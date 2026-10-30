<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockAdjustmentController extends Controller
{
    // 1. หน้ารายการปรับปรุงสต็อก
    public function index()
    {
        $adjustments = StockAdjustment::with(['product', 'user'])
            ->latest()
            ->paginate(15);

        return view('stock-adjustments.index', compact('adjustments'));
    }

    // 2. หน้าเพิ่มการปรับปรุง
    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('stock-adjustments.create', compact('products'));
    }

    // 3. บันทึกการปรับปรุง
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:addition,subtraction',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|in:damaged,lost,stocktake,return,other',
            'note' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // ล็อกสินค้าเพื่อป้องกันการแก้ไขซ้อนกัน
                $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

                $stockBefore = $product->stock;
                $newStock = $validated['type'] === 'addition'
                    ? $stockBefore + $validated['quantity']
                    : $stockBefore - $validated['quantity'];

                // ป้องกันสต็อกติดลบ
                if ($newStock < 0) {
                    throw new \DomainException("สต็อกไม่เพียงพอ (คงเหลือ {$stockBefore})");
                }

                // สร้างเลขที่ใบปรับปรุง
                // เลขชั่วคราว แล้วอัปเดตจาก id จริง (กันเลขชนกัน)
                $adjustment = StockAdjustment::create([
                    'adj_no' => 'TMP-' . Str::uuid(),
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => $validated['type'],
                    'quantity' => $validated['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $newStock,
                    'reason' => $validated['reason'],
                    'note' => $validated['note'] ?? null,
                ]);

                $adjNo = 'ADJ-' . date('Ymd') . '-' . str_pad($adjustment->id, 6, '0', STR_PAD_LEFT);
                $adjustment->update(['adj_no' => $adjNo]);

                // อัปเดตสต็อก
                $product->update(['stock' => $newStock]);

                return redirect()->route('stock-adjustments.index')
                    ->with('success', 'ปรับปรุงสต็อกสำเร็จ! เลขที่ ' . $adjNo);
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง')->withInput();
        }
    }
}
