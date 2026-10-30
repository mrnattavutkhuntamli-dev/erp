<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockDocument;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'user'])->latest()->paginate(15);
        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('purchase-orders.create', compact('suppliers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validate ข้อมูลการสั่งซื้อ
        $validated = $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'order_date'         => 'required|date',
            'expected_date'      => 'nullable|date|after_or_equal:order_date',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct', // ป้องกันสินค้าซ้ำในใบเดียวกัน
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
        ]);

        try {
            // 2. ใช้ Database Transaction เพื่อความปลอดภัยของข้อมูล
            return DB::transaction(function () use ($validated) {

                // คำนวณยอดเงินรวมทั้งหมด (total_amount) ของใบสั่งซื้อ
                $totalAmount = collect($validated['items'])->sum(function ($item) {
                    return $item['quantity'] * $item['unit_cost'];
                });

                // สร้างเลขที่ใบสั่งซื้ออัตโนมัติ (ตัวอย่าง: PO-20261020-A1B2)
                $poNo = 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(4));

                // 3. บันทึกข้อมูลหัวบิลลงตาราง purchase_orders
                $purchaseOrder = PurchaseOrder::create([
                    'po_no'         => $poNo,
                    'supplier_id'   => $validated['supplier_id'],
                    'user_id'       => auth()->id(), // ดึง ID ของ User ที่กำลัง Login อยู่
                    'order_date'    => $validated['order_date'],
                    'expected_date' => $validated['expected_date'] ?? null,
                    'status'        => 'pending', // สถานะเริ่มต้นของใบสั่งซื้อ
                    'total_amount'  => $totalAmount,
                    'note'          => $validated['note'] ?? null,
                ]);

                // 4. แปลงข้อมูลรายการสินค้าเพื่อเตรียมบันทึกในครั้งเดียว (Bulk Insert)
                $itemsData = collect($validated['items'])->map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'unit_cost'  => $item['unit_cost'],
                        'total'      => $item['quantity'] * $item['unit_cost'],
                    ];
                })->toArray();

                // บันทึกรายการสินค้าทั้งหมดลงตาราง purchase_order_items โดยผูกกับ $purchaseOrder
                $purchaseOrder->items()->createMany($itemsData);

                // 5. เมื่อบันทึกสำเร็จ Redirect กลับพร้อมข้อความแจ้งเตือน
                return redirect()->route('purchase-orders.index')
                    ->with('success', 'สร้างใบสั่งซื้อเลขที่ ' . $poNo . ' สำเร็จเรียบร้อยแล้ว');
            });
        } catch (\Exception $e) {
            // ถ้ามี Error ให้เด้งกลับไปหน้าเดิมพร้อมแจ้งเตือน
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['items.product', 'supplier', 'user']);
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        return DB::transaction(function () use ($purchaseOrder) {
            $po = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);

            if ($po->status !== 'pending') {
                return back()->with('error', 'ไม่สามารถรับของได้ เนื่องจาก PO นี้สถานะเป็น ' . $po->status . ' แล้ว');
            }

            //สร้าง Stockdocument
            $stockDoc = StockDocument::create([
                'doc_no'        => 'IN-PO-' . $po->po_no,
                'type'          => 'in',
                'date'          => now()->toDateString(),
                'receiver_name' => 'รับจาก ' . $po->supplier->name,
                'note'          => 'รับของจาก PO ' . $po->po_no,
                'user_id'       => auth()->id(),
                'status'        => 'completed',
            ]);

            foreach ($po->items as $item) {
                $product  = Product::lockForUpdate()->findOrFail($item->product_id);
                $oldStock = $product->stock;
                // ✅ เพิ่มสต็อก
                $product->increment('stock', $item->quantity);
                $newStock = $product->fresh()->stock;

                // ✅ อัปเดตต้นทุนล่าสุด (ใช้ราคาทุนจาก PO)
                $product->update(['cost' => $item->unit_cost]);

                // ✅ บันทึกรายการใน StockDocument
                $stockDoc->items()->create([
                    'product_id'  => $product->id,
                    'quantity'    => $item->quantity,
                    'stock_after' => $newStock,
                ]);
            }

            $po->update(['status' => 'received']);

            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', 'รับของและเพิ่มสต็อกเรียบร้อยแล้ว!');
        });
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return back()->with('error', 'ไม่สามารถยกเลิกได้ เนื่องจาก PO นี้ไม่ใช่สถานะ pending');
        }

        $purchaseOrder->update(['status' => 'cancelled']);
        return redirect()->route('purchase-orders.index')
            ->with('success', 'ยกเลิก PO ' . $purchaseOrder->po_no . ' สำเร็จ');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return back()->with('error', 'ไม่สามารถลบได้ เนื่องจาก PO นี้อยู่ระหว่างดำเนินการหรือเสร็จสิ้นแล้ว (ใช้การยกเลิกแทน)');
        }

        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'ลบใบสั่งซื้อสำเร็จ');
    }
}
