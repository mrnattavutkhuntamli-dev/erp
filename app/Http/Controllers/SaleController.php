<?php
namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // ไม่ให้เข้าหน้า POS ถ้ายังไม่ถึงกะ/หมดกะ พาไปหน้าเปิด-ปิดกะพร้อมบอกเหตุผล
        if ($reason = CashierShift::sellBlockReason(auth()->user())) {
            return redirect()->route('cashier-shifts.index')->with('error', $reason);
        }

        $categories = Category::orderBy('name')->get();

        $products = Product::with('primaryImage:id,product_id,image_path')
            ->where('is_active', true)
        // แสดงสินค้าที่หมดสต็อกด้วย (หน้าจอจะทำให้กดไม่ได้และขึ้น "หมด") เพื่อให้เห็นว่ามีสินค้านี้ในร้าน
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock', 'category_id']);

        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.pos', compact('products', 'customers', 'categories'));
    }

    public function history()
    {
        $sales = Sale::with(['customer', 'user'])
            ->latest()
            ->paginate(15);

        // ยอดขายวันนี้สุทธิ = ยอดขายวันนี้ - ยอดรับคืนที่บันทึกวันนี้ (ให้ตรงกับหน้ารายงาน)
        $todaySales = (float) Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total')
            - (float) \App\Models\SaleReturn::whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('total_value');

        return view('sales.history', compact('sales', 'todaySales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'        => ['nullable', Rule::exists('customers', 'id')->where('is_active', true)],
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1|max:999999',
            'discount'           => 'nullable|numeric|min:0|max:9999999999.99',
            'payment_method'     => 'required|in:cash,transfer,credit_card,credit',
            'received_amount'    => 'nullable|numeric|min:0|max:9999999999.99',
            'idempotency_key'    => 'nullable|uuid',
        ]);

        if ($validated['payment_method'] === 'credit' && empty($validated['customer_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถขายเชื่อให้ลูกค้าทั่วไปได้ กรุณาเลือกลูกค้าที่มีข้อมูล',
            ], 422);
        }

        if ($reason = CashierShift::sellBlockReason(auth()->user())) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }

        try {
            return DB::transaction(function () use ($validated) {
                // ✅ Idempotency
                if (! empty($validated['idempotency_key'])) {
                    $existing = Sale::where('idempotency_key', $validated['idempotency_key'])->where('user_id', auth()->id())->first();
                    if ($existing) {
                        return response()->json([
                            'success'      => true,
                            'sale_id'      => $existing->id,
                            'redirect_url' => route('sales.show', $existing->id),
                        ]);
                    }
                }

                // ✅ ข้อ 3: คำนวณเป็นสตางค์ (int) ทั้งหมด
                $subtotalCents = 0;
                $saleItemsData = [];

                // รวมสินค้าซ้ำ + เรียงตาม product_id (กัน deadlock)
                $lines = collect($validated['items'])
                    ->groupBy('product_id')
                    ->map(fn($g, $id) => [
                        'product_id' => (int) $id,
                        'quantity'   => (int) $g->sum('quantity'),
                    ])
                    ->sortKeys()
                    ->values();

                foreach ($lines as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    if (! $product->is_active) {
                        throw new \DomainException("สินค้า '{$product->name}' ถูกปิดการขาย");
                    }
                    if ($product->stock < $item['quantity']) {
                        throw new \DomainException("สินค้า '{$product->name}' มีสต็อกไม่พอ (เหลือ {$product->stock})");
                    }

                    $unitCents      = Money::toCents($product->price);
                    $itemTotalCents = $unitCents * (int) $item['quantity'];
                    $subtotalCents  += $itemTotalCents;

                    $saleItemsData[] = [
                        'product'      => $product,
                        'quantity'     => $item['quantity'],
                        'price_cents'  => $unitCents,
                        'cost_at_sale' => $product->cost,
                        'total_cents'  => $itemTotalCents,
                    ];
                }

                $discountCents = Money::toCents($validated['discount'] ?? 0);

                if ($subtotalCents > 999999999999) {
                    throw new \DomainException('ยอดรวมสูงเกินกว่าที่ระบบรองรับ (สูงสุด ฿9,999,999,999.99)');
                }
                if ($discountCents > $subtotalCents) {
                    throw new \DomainException(
                        'ส่วนลด (฿' . Money::format($discountCents) . ') เกินยอดรวม (฿' . Money::format($subtotalCents) . ')'
                    );
                }

                // ✅ ข้อ 2: จำกัดเพดานส่วนลดของ non-admin
                $user = auth()->user();
                if (! $user->hasRole('admin')) {
                    $maxPercent = (float) config('pos.max_discount_percent', 0);
                    if ($maxPercent > 0 && $subtotalCents > 0) {
                        $discountPercent = ($discountCents / $subtotalCents) * 100;
                        if ($discountPercent > $maxPercent + 1e-9) {
                            throw new \DomainException(
                                'ส่วนลดสูงสุดที่อนุญาตคือ ' . rtrim(rtrim(number_format($maxPercent, 2, '.', ''), '0'), '.') . '%'
                                . ' (ปัจจุบัน ' . round($discountPercent, 2) . '%)'
                            );
                        }
                    }
                }

                $totalCents = $subtotalCents - $discountCents;
                $isCredit   = $validated['payment_method'] === 'credit';

                if ($isCredit) {
                    $receivedCents = 0;
                    $changeCents   = 0;
                } else {
                    // เงินสดต้องระบุยอดรับมา; โอน/บัตร ถ้าไม่ส่งมาให้ถือว่าชำระครบพอดี
                    $receivedCents = Money::toCents(
                        $validated['received_amount']
                            ?? ($validated['payment_method'] === 'cash' ? 0 : Money::fromCents($totalCents))
                    );
                    $changeCents   = $receivedCents - $totalCents;

                    if ($changeCents < 0) {
                        throw new \DomainException(
                            'เงินที่รับมาไม่เพียงพอ (ต้องชำระอีก ' . Money::format(abs($changeCents)) . ' บาท)'
                        );
                    }
                }

                // ✅ ข้อ 8: ผูกบิลกับกะที่เปิดอยู่ของผู้ใช้
                $currentShift = CashierShift::currentFor(auth()->user());

                $sale = Sale::create([
                    'invoice_no'       => 'TMP-' . Str::uuid(),
                    'customer_id'      => $validated['customer_id'] ?? null,
                    'user_id'          => auth()->id(),
                    'cashier_shift_id' => $currentShift?->id,
                    'subtotal'         => Money::fromCents($subtotalCents),
                    'discount'         => Money::fromCents($discountCents),
                    'total'            => Money::fromCents($totalCents),
                    'payment_method'   => $validated['payment_method'],
                    'payment_status'   => $isCredit ? 'unpaid' : 'paid',
                    'paid_amount'      => $isCredit ? '0.00' : Money::fromCents($totalCents),
                    'due_date'         => $isCredit ? now()->addDays((int) config('pos.credit_days'))->toDateString() : null,
                    'received_amount'  => Money::fromCents($receivedCents),
                    'change_amount'    => Money::fromCents($changeCents),
                    'status'           => 'completed',
                    'idempotency_key'  => $validated['idempotency_key'] ?? null,
                ]);

                // อัปเดตเลข INV จริงจาก ID
                $sale->update([
                    'invoice_no' => 'INV-' . date('Ymd') . '-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                ]);

                foreach ($saleItemsData as $data) {
                    $data['product']->decrement('stock', $data['quantity']);
                    $newStock = $data['product']->fresh()->stock;

                    $sale->items()->create([
                        'product_id'   => $data['product']->id,
                        'quantity'     => $data['quantity'],
                        'price'        => Money::fromCents($data['price_cents']),
                        'cost_at_sale' => $data['cost_at_sale'],
                        'total'        => Money::fromCents($data['total_cents']),
                        'stock_after'  => $newStock,
                    ]);
                }

                return response()->json([
                    'success'      => true,
                    'sale_id'      => $sale->id,
                    'redirect_url' => route('sales.show', $sale->id),
                ]);
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (UniqueConstraintViolationException $e) {
            // ชน idempotency_key → คืนบิลเดิม
            if (! empty($validated['idempotency_key'])) {
                $existing = Sale::where('idempotency_key', $validated['idempotency_key'])->where('user_id', auth()->id())->first();
                if ($existing) {
                    return response()->json([
                        'success'      => true,
                        'sale_id'      => $existing->id,
                        'redirect_url' => route('sales.show', $existing->id),
                    ]);
                }
            }
            report($e);
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'], 500);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'], 500);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'user', 'customer', 'voidedBy']);
        return view('sales.receipt', compact('sale'));
    }

    public function destroy(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($sale, $validated) {
                $locked = Sale::lockForUpdate()->findOrFail($sale->id);

                if ($locked->status === 'voided') {
                    throw new \DomainException('ใบเสร็จนี้ถูกยกเลิกแล้ว');
                }

                // ✅ ข้อ 8: ห้าม void บิลในกะที่ปิดแล้ว
                if ($locked->cashier_shift_id) {
                    $shift = CashierShift::find($locked->cashier_shift_id);
                    if ($shift && $shift->isClosed()) {
                        throw new \DomainException(
                            'ไม่สามารถยกเลิกบิลในกะที่ปิดแล้วได้ (กะ#' . $shift->id . ' ปิดเมื่อ '
                            . optional($shift->closed_at)->format('d/m/Y H:i') . ')'
                        );
                    }
                }

                $activeReceipt = $locked->receipts()->where('status', 'active')->first();
                if ($activeReceipt) {
                    throw new \DomainException(
                        'ไม่สามารถยกเลิกบิลนี้ได้ เพราะมีการรับชำระเงินแล้ว กรุณายกเลิกใบรับเงิน ' . $activeReceipt->receipt_no . ' ก่อน'
                    );
                }

                // ✅ ห้าม void บิลที่มีการรับคืนสินค้าแล้ว (ไม่งั้นสต็อกและยอดคืนเงินจะซ้ำซ้อน)
                if ($locked->returns()->where('status', 'completed')->exists()) {
                    throw new \DomainException('ไม่สามารถยกเลิกบิลนี้ได้ เพราะมีการรับคืนสินค้าแล้ว');
                }

                foreach ($locked->items()->orderBy('product_id')->get() as $item) {
                    Product::lockForUpdate()->findOrFail($item->product_id)
                        ->increment('stock', $item->quantity);
                }

                $locked->update([
                    'status'      => 'voided',
                    'voided_at'   => now(),
                    'voided_by'   => auth()->id(),
                    'void_reason' => $validated['void_reason'],
                ]);

                return redirect()->route('sales.history')
                    ->with('success', 'ยกเลิกใบเสร็จ ' . $locked->invoice_no . ' สำเร็จ');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        }
    }
}
