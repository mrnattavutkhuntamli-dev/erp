<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\StockDocument;
use App\Models\Supplier;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'supplier_id'        => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'order_date'         => 'required|date',
            'expected_date'      => 'nullable|date|after_or_equal:order_date',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where('is_active', true)], // ป้องกันสินค้าซ้ำในใบเดียวกัน + ต้องเป็นสินค้าที่ใช้งานอยู่
            'items.*.quantity'   => 'required|integer|min:1|max:999999',
            'items.*.unit_cost'  => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
        ]);

        try {
            // 2. ใช้ Database Transaction เพื่อความปลอดภัยของข้อมูล
            return DB::transaction(function () use ($validated) {

                // คำนวณยอดเงินรวมทั้งหมด (total_amount) ของใบสั่งซื้อ
                $totalCents = collect($validated['items'])->sum(function ($item) {
                    return Money::toCents($item['unit_cost']) * (int) $item['quantity'];
                });
                if ($totalCents > 999999999999) {
                    throw new \DomainException('ยอดรวมสูงเกินกว่าที่ระบบรองรับ (สูงสุด ฿9,999,999,999.99)');
                }
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
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
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
        $purchaseOrder->load(['items.product', 'supplier', 'user', 'payments.user']);
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        try {
            return $this->doReceive($purchaseOrder);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาดระหว่างรับของ กรุณาลองใหม่อีกครั้ง');
        }
    }

    private function doReceive(PurchaseOrder $purchaseOrder)
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

                // ✅ อัปเดตต้นทุนแบบถัวเฉลี่ยถ่วงน้ำหนัก (สต็อกเดิม × ต้นทุนเดิม + ของใหม่ × ราคา PO)
                // ถ้าไม่มีสต็อกเดิม หรือยังไม่เคยตั้งต้นทุน → ใช้ราคาจาก PO ตรง ๆ
                $oldQtyCents  = max(0, (int) $oldStock);
                $oldCostCents = Money::toCents($oldCost ?? 0);
                $newCostCents = Money::toCents($item->unit_cost);
                $avgCents = ($oldQtyCents > 0 && $oldCostCents > 0)
                    ? (int) round(($oldQtyCents * $oldCostCents + (int) $item->quantity * $newCostCents)
                        / ($oldQtyCents + (int) $item->quantity))
                    : $newCostCents;
                $avgCost = Money::fromCents($avgCents);
                $product->update(['cost' => $avgCost]);

                // ✅ บันทึกรายการใน StockDocument
                $stockDoc->items()->create([
                    'product_id'  => $product->id,
                    'quantity'    => $item->quantity,
                    'stock_after' => $newStock,
                    'cost_before' => $oldCost,   // เก็บไว้ เพื่อคืนต้นทุนเดิมถ้าภายหลัง void ใบรับเข้า
                    'cost_after'  => $avgCost,
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
            'amount'          => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        try {
            return DB::transaction(function () use ($purchaseOrder, $validated) {
                $po = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);

                // กดซ้ำ/ส่งซ้ำด้วย key เดิม → ไม่บันทึกซ้ำ
                if (! empty($validated['idempotency_key'])
                    && PurchaseOrderPayment::where('idempotency_key', $validated['idempotency_key'])->exists()) {
                    return back()->with('success', 'รายการจ่ายเงินนี้ถูกบันทึกไปแล้ว');
                }

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

                PurchaseOrderPayment::create([
                    'purchase_order_id' => $po->id,
                    'user_id'           => auth()->id(),
                    'amount'            => Money::fromCents($paySatang),
                    'status'            => 'active',
                    'idempotency_key'   => $validated['idempotency_key'] ?? null,
                ]);

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

    /**
     * ยกเลิกรายการจ่ายเงินที่บันทึกผิด → คืนยอดค้างจ่ายของ PO (และปลดล็อกการ void ใบรับเข้า)
     */
    public function voidPayment(PurchaseOrder $purchaseOrder, PurchaseOrderPayment $payment)
    {
        try {
            return DB::transaction(function () use ($purchaseOrder, $payment) {
                $po  = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);
                $pay = PurchaseOrderPayment::lockForUpdate()->findOrFail($payment->id);

                if ((int) $pay->purchase_order_id !== (int) $po->id) {
                    abort(404);
                }
                if ($pay->status !== 'active') {
                    throw new \DomainException('รายการจ่ายเงินนี้ถูกยกเลิกไปแล้ว');
                }

                $newPaidSatang = (int) round((float) $po->paid_amount * 100) - (int) round((float) $pay->amount * 100);
                $po->update(['paid_amount' => Money::fromCents(max(0, $newPaidSatang))]);

                $pay->update([
                    'status'    => 'voided',
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                ]);

                return back()->with('success', 'ยกเลิกรายการจ่ายเงิน ฿' . number_format((float) $pay->amount, 2) . ' สำเร็จ');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        }
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);

        // อัปเดตแบบ atomic กันชนกับการรับของ
        $updated = PurchaseOrder::where('id', $purchaseOrder->id)
            ->where('status', 'pending')
            ->update([
                'status'        => 'cancelled',
                'cancel_reason' => $validated['cancel_reason'],
                'cancelled_at'  => now(),
                'cancelled_by'  => auth()->id(),
            ]);

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
        // ไม่ลบ PO จริงเพื่อรักษาร่องรอยการตรวจสอบ ให้ใช้การยกเลิก (ระบุเหตุผล) แทน
        return back()->with('error', 'ไม่อนุญาตให้ลบ PO กรุณาใช้การยกเลิกพร้อมระบุเหตุผลแทน');
    }
}
