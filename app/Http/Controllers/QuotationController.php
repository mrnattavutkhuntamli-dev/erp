<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    // 1. หน้ารายการใบเสนอราคา
    public function index()
    {
        $this->markExpired();

        $quotations = Quotation::with(['customer', 'user'])
            ->latest()
            ->paginate(15);

        return view('quotations.index', compact('quotations'));
    }

    // 2. หน้าสร้างใบเสนอราคา
    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('quotations.create', compact('customers', 'products'));
    }

    // 3. บันทึกใบเสนอราคา
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'        => ['nullable', Rule::exists('customers', 'id')->where('is_active', true)],
            'quote_date'         => 'required|date',
            'valid_until'        => 'required|date|after_or_equal:quote_date|after_or_equal:today',
            'discount'           => 'nullable|numeric|min:0|max:99999999.99|decimal:0,2',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity'   => 'required|integer|min:1|max:999999',
            'items.*.price'      => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // คำนวณยอดรวม (เป็นสตางค์ทั้งหมด กัน float ผิดพลาด)
                $subtotalCents = collect($validated['items'])
                    ->sum(fn ($i) => Money::toCents($i['price']) * (int) $i['quantity']);
                $discountCents = Money::toCents($validated['discount'] ?? 0);
                if ($subtotalCents > 999999999999) {
                    throw new \DomainException('ยอดรวมสูงเกินกว่าที่ระบบรองรับ (สูงสุด ฿9,999,999,999.99)');
                }
                if ($discountCents > $subtotalCents) {
                    throw new \DomainException("ส่วนลดเกินยอดรวม");
                }
                $totalCents = $subtotalCents - $discountCents;

                // ผู้ใช้ที่ไม่ใช่ admin: ห้ามตั้งราคาต่ำกว่าต้นทุน และจำกัดส่วนลดตามเพดานที่กำหนด
                if (! auth()->user()->hasRole('admin')) {
                    $costs = Product::whereIn('id', collect($validated['items'])->pluck('product_id'))
                        ->pluck('cost', 'id');
                    foreach ($validated['items'] as $i) {
                        if (Money::toCents($i['price']) < Money::toCents($costs[$i['product_id']] ?? 0)) {
                            throw new \DomainException('ไม่สามารถตั้งราคาขายต่ำกว่าต้นทุนได้ (ต้องให้ผู้ดูแลระบบเป็นผู้ดำเนินการ)');
                        }
                    }
                    $maxDiscountCents = $subtotalCents * (float) config('pos.max_discount_percent') / 100;
                    if ($discountCents > $maxDiscountCents + 1e-6) {
                        throw new \DomainException('ส่วนลดเกินเพดาน ' . config('pos.max_discount_percent') . '% ของยอดรวม (ต้องให้ผู้ดูแลระบบอนุมัติ)');
                    }
                }

                // สร้างเลขที่ใบเสนอราคา
                $quoteNo = 'TMP-' . Str::uuid();   // เลขจริงอัปเดตจาก id ด้านล่าง (กันเลขชนกัน)

                // บันทึกหัวเอกสาร
                $quotation = Quotation::create([
                    'quote_no'    => $quoteNo,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'user_id'     => auth()->id(),
                    'quote_date'  => $validated['quote_date'],
                    'valid_until' => $validated['valid_until'],
                    'subtotal'    => Money::fromCents($subtotalCents),
                    'discount'    => Money::fromCents($discountCents),
                    'total'       => Money::fromCents($totalCents),
                    'status'      => 'draft',
                    'note'        => $validated['note'] ?? null,
                ]);

                $quoteNo = 'QT-' . date('Ymd') . '-' . str_pad($quotation->id, 6, '0', STR_PAD_LEFT);
                $quotation->update(['quote_no' => $quoteNo]);

                // บันทึกรายการสินค้า
                foreach ($validated['items'] as $item) {
                    $quotation->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => Money::fromCents(Money::toCents($item['price'])),
                        'total'      => Money::fromCents(Money::toCents($item['price']) * (int) $item['quantity']),
                    ]);
                }

                return redirect()->route('quotations.show', $quotation->id)
                    ->with('success', 'สร้างใบเสนอราคา ' . $quoteNo . ' สำเร็จ!');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง')->withInput();
        }
    }

    // 4. หน้ารายละเอียด
    public function show(Quotation $quotation)
    {
        $this->markExpired();
        $quotation->refresh();
        $quotation->load(['items.product', 'customer', 'user']);
        return view('quotations.show', compact('quotation'));
    }

    // 5. อัปเดตสถานะ (เช่น sent, rejected)
    public function updateStatus(Request $request, Quotation $quotation)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,rejected',
        ]);

        if (in_array($quotation->status, ['accepted', 'expired'], true)) {
            return back()->with('error', 'ไม่สามารถเปลี่ยนสถานะใบเสนอราคาที่เป็น ' . $quotation->status . ' แล้ว');
        }

        $quotation->update(['status' => $request->status]);

        return back()->with('success', 'อัปเดตสถานะสำเร็จ!');
    }
        public function convertToSale(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'payment_method'  => 'required|in:cash,transfer,credit_card,credit',
            'received_amount' => 'nullable|numeric|min:0|max:9999999999.99',
            'idempotency_key' => 'nullable|uuid',
        ]);

        if ($reason = \App\Models\CashierShift::sellBlockReason(auth()->user())) {
            return back()->with('error', $reason);
        }

        try {
            return DB::transaction(function () use ($validated, $quotation) {
                // ล็อกใบเสนอราคาก่อนเช็คสถานะ กันกดแปลงซ้ำพร้อมกัน
                $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);

                // กดซ้ำด้วย key เดิม → พาไปที่บิลเดิม
                if (! empty($validated['idempotency_key'])) {
                    $existing = Sale::where('idempotency_key', $validated['idempotency_key'])->first();
                    if ($existing && (int) $existing->user_id !== (int) auth()->id()) {
                        throw new \DomainException('รหัสป้องกันบันทึกซ้ำชนกับรายการของผู้ใช้อื่น กรุณารีเฟรชหน้าแล้วลองใหม่');
                    }
                    if ($existing) {
                        return redirect()->route('sales.show', $existing->id)
                            ->with('success', 'รายการนี้ถูกบันทึกไปแล้ว');
                    }
                }

                if (! in_array($quotation->status, ['draft', 'sent'], true)) {
                    throw new \DomainException('ใบเสนอราคานี้แปลงเป็นใบเสร็จไม่ได้ (สถานะ: ' . $quotation->status . ')');
                }

                // ใบเสนอราคาหมดอายุ: บันทึกสถานะ expired (commit) แล้วแจ้งผู้ใช้ ไม่ throw เพื่อไม่ให้ rollback
                if ($quotation->valid_until && $quotation->valid_until->copy()->endOfDay()->isPast()) {
                    $quotation->update(['status' => 'expired']);
                    return back()->with('error', 'ใบเสนอราคานี้หมดอายุแล้ว (ใช้ได้ถึง ' . $quotation->valid_until->format('d/m/Y') . ') กรุณาออกใบเสนอราคาใหม่');
                }

                $isCredit = $validated['payment_method'] === 'credit';
                if ($isCredit && empty($quotation->customer_id)) {
                    throw new \DomainException('ไม่สามารถขายเชื่อให้ลูกค้าทั่วไปได้ กรุณาระบุลูกค้าในใบเสนอราคา');
                }
                if ($isCredit && ! Customer::where('id', $quotation->customer_id)->where('is_active', true)->exists()) {
                    throw new \DomainException('ลูกค้ารายนี้ถูกปิดใช้งานแล้ว ไม่สามารถขายเชื่อได้');
                }

                $totalCents = Money::toCents($quotation->total);
                if ($isCredit) {
                    $receivedCents = 0;
                    $changeCents   = 0;
                } else {
                    $receivedCents = Money::toCents($validated['received_amount'] ?? Money::fromCents($totalCents));
                    if ($receivedCents < $totalCents) {
                        throw new \DomainException('เงินที่รับมาไม่เพียงพอ (ต้องชำระอีก ' . Money::format($totalCents - $receivedCents) . ' บาท)');
                    }
                    $changeCents = $receivedCents - $totalCents;
                }

                // ล็อกสินค้าเรียงตาม id (กัน deadlock) แล้วตรวจสต็อก
                $items    = $quotation->items()->orderBy('product_id')->get();
                $products = [];
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);
                    if (! $product->is_active) {
                        throw new \DomainException("สินค้า '{$product->name}' ถูกปิดการขายแล้ว ไม่สามารถแปลงใบเสนอราคานี้ได้");
                    }
                    if ($product->stock < $item->quantity) {
                        throw new \DomainException("สินค้า '{$product->name}' มีสต็อกไม่พอ (เหลือ {$product->stock})");
                    }
                    $products[$item->id] = $product;
                }

                // เลขชั่วคราว (ไม่ชน) แล้วอัปเดตจาก id จริง — เหมือน Receipt
                // ผูกบิลกับกะที่เปิดอยู่ (เหมือน POS) เพื่อให้ยอดปิดกะและกฎห้าม void หลังปิดกะใช้ได้กับบิลนี้ด้วย
                $currentShift = \App\Models\CashierShift::currentForUpdate(auth()->user());
                if ($reason = \App\Models\CashierShift::sellBlockReason(auth()->user())) {
                    throw new \DomainException($reason);
                }

                $sale = Sale::create([
                    'invoice_no'       => 'TMP-' . Str::uuid(),
                    'customer_id'      => $quotation->customer_id,
                    'user_id'          => auth()->id(),
                    'cashier_shift_id' => $currentShift?->id,
                    'subtotal'        => $quotation->subtotal,
                    'discount'        => $quotation->discount,
                    'total'           => $quotation->total,
                    'payment_method'  => $validated['payment_method'],
                    'payment_status'  => $isCredit ? 'unpaid' : 'paid',
                    'paid_amount'     => $isCredit ? 0 : $quotation->total,
                    'due_date'        => $isCredit ? now()->addDays((int) config('pos.credit_days'))->toDateString() : null,
                    'received_amount' => Money::fromCents($receivedCents),
                    'change_amount'   => Money::fromCents($changeCents),
                    'status'          => 'completed',
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ]);
                $sale->update([
                    'invoice_no' => 'INV-' . date('Ymd') . '-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                ]);

                foreach ($items as $item) {
                    $product = $products[$item->id];
                    $product->decrement('stock', $item->quantity);

                    $sale->items()->create([
                        'product_id'   => $item->product_id,
                        'quantity'     => $item->quantity,
                        'price'        => $item->price,
                        'cost_at_sale' => $product->cost,
                        'total'        => $item->total,
                        'stock_after'  => $product->fresh()->stock,
                    ]);
                }

                $quotation->update(['status' => 'accepted']);

                return redirect()->route('sales.show', $sale->id)
                    ->with('success', 'แปลงเป็นใบเสร็จ ' . $sale->invoice_no . ' สำเร็จ!');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // ชน idempotency_key (กดซ้ำพร้อมกัน) → พาไปบิลเดิมถ้าเป็นของผู้ใช้คนเดียวกัน
            if (! empty($validated['idempotency_key'])) {
                $existing = Sale::where('idempotency_key', $validated['idempotency_key'])->where('user_id', auth()->id())->first();
                if ($existing) {
                    return redirect()->route('sales.show', $existing->id)->with('success', 'รายการนี้ถูกบันทึกไปแล้ว');
                }
            }
            report($e);
            return back()->with('error', 'ไม่สามารถแปลงเป็นใบเสร็จได้ กรุณาลองใหม่อีกครั้ง');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'ไม่สามารถแปลงเป็นใบเสร็จได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    // 7. Export PDF ใบเสนอราคา
    public function exportPdf(Quotation $quotation)
    {
        $quotation->load(['items.product', 'customer', 'user']);

        $pdf = Pdf::setOption([
            'fontDir'     => storage_path('fonts'),
            'fontCache'   => storage_path('fonts'),
            'tempDir'     => storage_path('fonts'),
            'defaultFont' => 'sans-serif',
        ]);

        $pdf->loadView('quotations.pdf', compact('quotation'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('quotation_' . $quotation->quote_no . '.pdf');
    }

    /**
     * ปรับสถานะใบเสนอราคาที่เลยวันใช้งานแล้ว (draft/sent) เป็น expired
     */
    private function markExpired(): void
    {
        Quotation::whereIn('status', ['draft', 'sent'])
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => 'expired']);
    }
}
