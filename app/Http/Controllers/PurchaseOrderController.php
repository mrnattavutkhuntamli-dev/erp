<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Support\Money;
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
            'items.*.unit_cost'  => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
        ]);

        try {
            // 2. ใช้ Database Transaction เพื่อความปลอดภัยของข้อมูล
            return DB::transaction(function () use ($validated) {

                // คำนวณยอดเงินรวมทั้งหมด (total_amount) ของใบสั่งซื้อ
                $totalCents = collect($validated['items'])->sum(function ($item) {
                    return Money::toCents($item['unit_cost']) * (int) $item['quantity'];
                });
                $totalAmount = Money::fromCents($totalCents);

                // สร้างเลขที่ใบสั่งซื้ออัตโนมัติ (ตัวอย่าง: PO-20261020-A1B2)
                $poNo = 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(8));

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
                        'unit_cost'  => Money::fromCents(Money::toCents($item['unit_cost'])),
                        'total'      => Money::fromCents(Money::toCents($item['unit_cost']) * (int) $item['quantity']),
                    ];
                })->toArray();

                // บันทึกรายการสินค้าทั้งหมดลงตาราง purchase_order_items โดยผูกกับ $purchaseOrder
                $purchaseOrder->items()->createMany($itemsData);

                // 5. เมื่อบันทึกสำเร็จ Redirect กลับพร้อมข้อความแจ้งเตือน
                return redirect()->route('purchase-orders.index')
                    ->with('success', 'สร้างใบสั่งซื้อเลขที่ ' . $poNo . ' สำเร็จเรียบร้อยแล้ว');
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง')->withInput();
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

            // เลขที่เอกสารรับเข้า: ถ้าเคยรับ→void แล้วรับใหม่ ต้องไม่ซ้ำกับเลขเดิม (doc_no เป็น unique)
            $receiveRound = StockDocument::where('purchase_order_id', $po->id)->count() + 1;
            $docNo        = 'IN-PO-' . $po->po_no . ($receiveRound > 1 ? '-R' . $receiveRound : '');

            //สร้าง Stockdocument (ผูกกับ PO เพื่อให้ void ใบรับเข้าแล้วย้อนสถานะ PO ได้)
            $stockDoc = StockDocument::create([
                'doc_no'            => $docNo,
                'type'              => 'in',
                'date'              => now()->toDateString(),
                'receiver_name'     => 'รับจาก ' . $po->supplier->name,
                'note'              => 'รับของจาก PO ' . $po->po_no,
                'user_id'           => auth()->id(),
                'status'            => 'completed',
                'purchase_order_id' => $po->id,
            ]);

            foreach ($po->items as $item) {
                $product  = Product::lockForUpdate()->findOrFail($item->product_id);
                $oldStock = $product->stock;
                $oldCost  = $product->cost;
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
                    'cost_before' => $oldCost,   // เก็บไว้ เพื่อคืนต้นทุนเดิมถ้าภายหลัง void ใบรับเข้า
                    'cost_after'  => $item->unit_cost,
                ]);
            }

            $po->update(['status' => 'received', 'received_at' => now()]);

            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', 'รับของและเพิ่มสต็อกเรียบร้อยแล้ว!');
        });
    }

    /**
     * บันทึกการจ่ายเงินให้ Supplier (จ่ายบางส่วนหรือเต็มจำนวน) — ใช้ตัดยอดในรายงานเจ้าหนี้ (A/P)
     */
    public function pay(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:9999999.99',
        ]);

        try {
            return DB::transaction(function () use ($purchaseOrder, $validated) {
                $po = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);

                if ($po->status !== 'received') {
                    throw new \DomainException('บันทึกการจ่ายเงินได้เฉพาะ PO ที่รับของแล้วเท่านั้น');
                }

                // คำนวณเป็นสตางค์ กัน floating point
                $remainingSatang = (int) round($po->remaining_amount * 100);
                $paySatang       = (int) round($validated['amount'] * 100);

                if ($remainingSatang <= 0) {
                    throw new \DomainException('PO นี้จ่ายเงินครบแล้ว');
                }
                if ($paySatang > $remainingSatang) {
                    throw new \DomainException('จำนวนเงินเกินยอดค้างจ่าย (฿' . number_format($remainingSatang / 100, 2) . ')');
                }

                $po->update(['paid_amount' => round(((float) $po->paid_amount * 100 + $paySatang) / 100, 2)]);

                return back()->with('success', 'บันทึกการจ่ายเงิน ฿' . number_format($paySatang / 100, 2) . ' สำเร็จ');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        // อัปเดตแบบ atomic กันชนกับการรับของ
        $updated = PurchaseOrder::where('id', $purchaseOrder->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        if (! $updated) {
            return back()->with('error', 'ไม่สามารถยกเลิกได้ เนื่องจาก PO นี้ไม่ใช่สถานะ pending');
        }

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
