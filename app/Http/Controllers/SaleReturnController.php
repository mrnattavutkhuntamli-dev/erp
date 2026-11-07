<?php
namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleReturnController extends Controller
{
    public function index()
    {
        $returns = SaleReturn::with(['sale.customer', 'user'])->latest()->paginate(15);

        return view('sale-returns.index', compact('returns'));
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale.customer', 'items.product', 'user', 'voidedBy']);

        return view('sale-returns.show', ['return' => $saleReturn]);
    }

    public function create(Sale $sale)
    {
        if ($sale->status !== 'completed') {
            return redirect()->route('sales.show', $sale->id)
                ->with('error', 'บิลนี้ถูกยกเลิกแล้ว ไม่สามารถรับคืนสินค้าได้');
        }

        $sale->load(['items.product', 'customer']);
        $returnedQty    = $this->returnedQtyBySaleItem($sale->id);
        $idempotencyKey = (string) Str::uuid();

        return view('sale-returns.create', compact('sale', 'returnedQty', 'idempotencyKey'));
    }

    public function store(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|integer|distinct',
            'items.*.quantity'     => 'required|integer|min:0',
            'items.*.restock'      => 'nullable|boolean',
            'refund_method'        => 'nullable|in:cash,transfer',
            'reason'               => 'required|string|max:500',
            'idempotency_key'      => 'nullable|uuid',
        ]);

        try {
            return DB::transaction(function () use ($validated, $sale) {
                $sale = Sale::lockForUpdate()->findOrFail($sale->id);

                // กดซ้ำ → คืนใบเดิม
                if (! empty($validated['idempotency_key'])) {
                    $existing = SaleReturn::where('idempotency_key', $validated['idempotency_key'])->first();
                    if ($existing) {
                        return redirect()->route('sale-returns.show', $existing->id)
                            ->with('success', 'รายการนี้ถูกบันทึกไปแล้ว');
                    }
                }

                if ($sale->status !== 'completed') {
                    throw new \DomainException('บิลนี้ถูกยกเลิกแล้ว ไม่สามารถรับคืนสินค้าได้');
                }

                $lines = collect($validated['items'])
                    ->filter(fn ($l) => (int) $l['quantity'] > 0)
                    ->values();

                if ($lines->isEmpty()) {
                    throw new \DomainException('กรุณาระบุจำนวนสินค้าที่รับคืนอย่างน้อย 1 รายการ');
                }

                $saleItems      = $sale->items()->with('product')->orderBy('id')->get()->keyBy('id');
                $returnedBefore = $this->returnedQtyBySaleItem($sale->id);

                $totalCents          = Money::toCents($sale->total);
                $subtotalCents       = Money::toCents($sale->subtotal);
                $returnedBeforeCents = Money::toCents($sale->returned_amount);
                $refundedBeforeCents = Money::toCents($sale->refunded_amount);
                $paidCents           = Money::toCents($sale->paid_amount);

                $rows      = [];
                $thisQty   = [];
                $valueCents = 0;

                foreach ($lines as $line) {
                    $item = $saleItems->get((int) $line['sale_item_id']);
                    if (! $item) {
                        throw new \DomainException('มีรายการที่ไม่อยู่ในบิลนี้');
                    }

                    $qty     = (int) $line['quantity'];
                    $already = (int) ($returnedBefore[$item->id] ?? 0);

                    if ($already + $qty > $item->quantity) {
                        $left = $item->quantity - $already;
                        throw new \DomainException(
                            "สินค้า '" . ($item->product->name ?? '-') . "' รับคืนได้อีกไม่เกิน {$left} ชิ้น (ขายไป {$item->quantity}, คืนแล้ว {$already})"
                        );
                    }

                    // มูลค่าสุทธิของบรรทัด = ยอดบรรทัด × (ยอดสุทธิบิล ÷ ยอดก่อนลด) → กระจายส่วนลดบิลตามสัดส่วน
                    $netLine = $subtotalCents > 0
                        ? (int) round(Money::toCents($item->total) * $totalCents / $subtotalCents)
                        : 0;

                    // คิดแบบสะสม กันเศษสตางค์เพี้ยนเมื่อคืนหลายครั้ง
                    $valueBefore = (int) round($netLine * $already / $item->quantity);
                    $valueAfter  = (int) round($netLine * ($already + $qty) / $item->quantity);
                    $lineValue   = $valueAfter - $valueBefore;

                    $valueCents += $lineValue;
                    $thisQty[$item->id] = $qty;

                    $rows[] = [
                        'sale_item_id' => $item->id,
                        'product_id'   => (int) $item->product_id,
                        'quantity'     => $qty,
                        'value_cents'  => $lineValue,
                        'restock'      => (bool) ($line['restock'] ?? false),
                    ];
                }

                // คืนครบทุกรายการ → ปรับเศษสตางค์ให้มูลค่ารับคืนรวมเท่ากับยอดบิลพอดี
                $allReturned = $saleItems->every(
                    fn ($it) => ((int) ($returnedBefore[$it->id] ?? 0) + (int) ($thisQty[$it->id] ?? 0)) >= $it->quantity
                );
                if ($allReturned) {
                    $diff = ($totalCents - $returnedBeforeCents) - $valueCents;
                    $rows[count($rows) - 1]['value_cents'] += $diff;
                    $valueCents += $diff;
                }

                // เงินที่ต้องคืนลูกค้า = เงินที่ลูกค้าจ่ายสุทธิ - ยอดที่ลูกค้าต้องจ่ายจริงหลังคืนของ (ถ้าติดลบ = 0)
                $netOwedAfter = $totalCents - $returnedBeforeCents - $valueCents;
                $refundCents  = max(0, ($paidCents - $refundedBeforeCents) - $netOwedAfter);

                $refundMethod = null;
                if ($refundCents > 0) {
                    $refundMethod = $validated['refund_method'] ?? null;
                    if (! $refundMethod) {
                        throw new \DomainException('กรุณาเลือกวิธีคืนเงิน (ต้องคืนเงินลูกค้า ฿' . Money::format($refundCents) . ')');
                    }
                    // คืนเงินสดต้องมีกะเปิดอยู่ (ตามกติกาเดียวกับการขาย)
                    if ($refundMethod === 'cash' && ($reason = CashierShift::sellBlockReason(auth()->user()))) {
                        throw new \DomainException($reason);
                    }
                }

                $shift = CashierShift::currentFor(auth()->user());

                $return = SaleReturn::create([
                    'return_no'        => 'TMP-' . Str::uuid(),
                    'sale_id'          => $sale->id,
                    'user_id'          => auth()->id(),
                    'cashier_shift_id' => $shift?->id,
                    'total_value'      => Money::fromCents($valueCents),
                    'refund_amount'    => Money::fromCents($refundCents),
                    'refund_method'    => $refundMethod,
                    'reason'           => $validated['reason'],
                    'status'           => 'completed',
                    'idempotency_key'  => $validated['idempotency_key'] ?? null,
                ]);

                $return->update([
                    'return_no' => 'RT-' . date('Ymd') . '-' . str_pad($return->id, 6, '0', STR_PAD_LEFT),
                ]);

                // เรียงตาม product_id กัน deadlock
                foreach (collect($rows)->sortBy('product_id') as $row) {
                    $stockAfter = null;

                    if ($row['restock']) {
                        $product = Product::lockForUpdate()->findOrFail($row['product_id']);
                        $product->increment('stock', $row['quantity']);
                        $stockAfter = $product->fresh()->stock;
                    }

                    $return->items()->create([
                        'sale_item_id' => $row['sale_item_id'],
                        'product_id'   => $row['product_id'],
                        'quantity'     => $row['quantity'],
                        'value'        => Money::fromCents($row['value_cents']),
                        'restock'      => $row['restock'],
                        'stock_after'  => $stockAfter,
                    ]);
                }

                // อัปเดตบิล: ยอดรับคืน/ยอดคืนเงินสะสม + สถานะการชำระ
                $newReturned = $returnedBeforeCents + $valueCents;
                $newRefunded = $refundedBeforeCents + $refundCents;
                $netPaid     = $paidCents - $newRefunded;
                $outstanding = $totalCents - $newReturned - $netPaid;

                $sale->update([
                    'returned_amount' => Money::fromCents($newReturned),
                    'refunded_amount' => Money::fromCents($newRefunded),
                    'payment_status'  => $outstanding <= 0 ? 'paid' : ($netPaid > 0 ? 'partial' : 'unpaid'),
                ]);

                return redirect()->route('sale-returns.show', $return->id)
                    ->with('success', 'บันทึกรับคืน ' . $return->fresh()->return_no . ' สำเร็จ'
                        . ($refundCents > 0 ? ' (คืนเงิน ฿' . Money::format($refundCents) . ')' : ''));
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (UniqueConstraintViolationException $e) {
            if (! empty($validated['idempotency_key'])) {
                $existing = SaleReturn::where('idempotency_key', $validated['idempotency_key'])->first();
                if ($existing) {
                    return redirect()->route('sale-returns.show', $existing->id);
                }
            }
            report($e);

            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง')->withInput();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง')->withInput();
        }
    }

    /** จำนวนที่รับคืนไปแล้วของแต่ละรายการในบิล (นับเฉพาะใบรับคืนที่ยังไม่ถูกยกเลิก) */
    private function returnedQtyBySaleItem(int $saleId)
    {
        return DB::table('sale_return_items as i')
            ->join('sale_returns as r', 'r.id', '=', 'i.sale_return_id')
            ->where('r.sale_id', $saleId)
            ->where('r.status', 'completed')
            ->groupBy('i.sale_item_id')
            ->selectRaw('i.sale_item_id, SUM(i.quantity) as qty')
            ->pluck('qty', 'sale_item_id');
    }
}
