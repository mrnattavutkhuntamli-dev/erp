<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockDocument;
use App\Models\StockDocumentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockDocumentController extends Controller
{
    public function index()
    {
        $documents = StockDocument::with(['user'])->latest()->paginate(15);
        return view('stock_documents.index', compact('documents'));
    }

    public function create(Request $request) {
        // ดึงสินค้าที่เปิดใช้งานอยู่มาให้เลือก
        $products = Product::where('is_active',true)->orderBy('name')->get();
        //รีบค่า type จาก url
        $type = $request->query('type','in');
        return view('stock_documents.create',compact('products','type'));
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'date' => 'required|date',
            'receiver_name' => 'nullable|string|max:255',
            'note'          => 'nullable|string',
            // ตรวจสอบรายการสินค้า (ต้องมีอย่างน้อย 1 รายการ)
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        try {
            return DB::transaction(function () use ($validated) {
               // สร้างเอกสารอัตโนมัติ IN-20261018-0001
                $prefix  = $validated['type'] === 'in' ? 'IN' :'OUT';
                $dateStr = date('Ymd'); // ใช้จากวันที่เอกสาร
                $countToday = StockDocument::whereDate('created_at', today())->count() + 1;
                $docNo = $prefix . '-' . $dateStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);
                $document = StockDocument::create([
                    'doc_no' => $docNo,
                    'type' => $validated['type'],
                    'date' => $validated['date'],
                    'receiver_name' => $validated['receiver_name'] ?? null,
                    'note'          => $validated['note'] ?? null,
                    'user_id' => auth()->id(), // พนักงานที่ล็อกอินอยู่
                ]);

                // 3. วนลูปบันทึกรายการสินค้า (Details) และอัปเดตสต็อก
                foreach ($validated['items'] as $item) {
                    // ล็อกข้อมูลสินค้าไว้ป้องกันการแก้ไขซ้อนกัน
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    // คำนวณสต็อกใหม่
                    $newStock = $validated['type'] === 'in'
                        ? $product->stock + $item['quantity']
                        : $product->stock - $item['quantity'];

                    // ถ้าเป็นการเบิกออก (out) และสต็อกไม่พอ ให้โยน Error กลับไป
                    if ($newStock < 0) {
                        throw new \Exception("สินค้า '{$product->name}' มีสต็อกไม่เพียงพอ (คงเหลือ {$product->stock})");
                    }

                    // อัปเดตสต็อกในตาราง products
                    $product->update(['stock' => $newStock]);

                    // บันทึกรายการลงในใบเอกสาร
                    $document->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'stock_after' => $newStock,
                    ]);
                }
                return redirect()->route('stock-documents.show', $document->id)->with('success','สร้างเอกสาร '. $docNo. 'สำเร็จ');
            });
        }catch (\Exception $e) {
            // ถ้ามี Error ให้เด้งกลับไปหน้าเดิมพร้อมแจ้งเตือน
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
