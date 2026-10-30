<?php
namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::with(['sale.customer', 'user'])->latest()->paginate(15);
        return view('receipts.index', compact('receipts'));
    }

    public function create()
    {
        $unpaidSales = Sale::with('customer')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('status', 'completed')
            ->latest('id')
            ->get();
        $idempotencyKey = (string) Str::uuid();
        return view('receipts.create', compact('unpaidSales', 'idempotencyKey'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id'         => ['required', 'integer', 'exists:sales,id'],
            'payment_date'    => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount'          => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'payment_method'  => ['required', 'in:cash,transfer,credit_card'],
            'note'            => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'uuid'],
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $sale = Sale::lockForUpdate()->findOrFail($validated['sale_id']);

                if (! empty($validated['idempotency_key'])) {
                    $existing = Receipt::where('idempotency_key', $validated['idempotency_key'])->first();
                    if ($existing) {
                        return redirect()->route('receipts.show', $existing->id)
                            ->with('success', 'รายการนี้ถูกบันทึกไปแล้ว');
                    }
                }

                if ($sale->status !== 'completed') {
                    throw ValidationException::withMessages([
                        'sale_id' => 'ไม่สามารถรับชำระเงินได้ เนื่องจากบิลนี้ถูกยกเลิกหรือไม่สมบูรณ์',
                    ]);
                }

                if ($validated['payment_date'] < $sale->created_at->toDateString()) {
                    throw ValidationException::withMessages([
                        'payment_date' => 'วันที่ชำระเงินต้องไม่ก่อนวันที่ขาย',
                    ]);
                }

                $total     = $this->toSatang($sale->total);
                $paid      = $this->toSatang($sale->paid_amount);
                $amount    = $this->toSatang($validated['amount']);
                $remaining = max(0, $total - $paid);

                if ($amount > $remaining) {
                    throw ValidationException::withMessages([
                        'amount' => 'จำนวนเงินที่รับเกินยอดคงเหลือ (ค้างชำระ ฿' . number_format($remaining / 100, 2) . ')',
                    ]);
                }

                $receipt = Receipt::create([
                    'receipt_no'      => 'TMP-' . Str::uuid(),
                    'sale_id'         => $sale->id,
                    'user_id'         => auth()->id(),
                    'payment_date'    => $validated['payment_date'],
                    'amount'          => $this->fromSatang($amount),
                    'payment_method'  => $validated['payment_method'],
                    'note'            => $validated['note'] ?? null,
                    'status'          => 'active',
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ]);

                $receipt->update([
                    'receipt_no' => 'RC-' . now()->format('Ymd') . '-' . str_pad($receipt->id, 6, '0', STR_PAD_LEFT),
                ]);

                $newPaid = $paid + $amount;

                $sale->update([
                    'paid_amount'    => $this->fromSatang($newPaid),
                    'payment_status' => $newPaid >= $total ? 'paid' : 'partial',
                ]);

                return redirect()->route('receipts.show', $receipt->id)
                    ->with('success', 'บันทึกการรับชำระเงิน ' . $receipt->receipt_no . ' สำเร็จ!');
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'ไม่สามารถบันทึกการรับชำระเงินได้ กรุณาลองใหม่อีกครั้ง')
                ->withInput();
        }
    }

    /**
     * ✅ แก้ไข: รองรับทั้งการดู Receipt และการสร้างใหม่จาก ?sale_id=
     */
    public function show(Request $request, ?Receipt $receipt = null)
    {
        // กรณีเข้าด้วย ?sale_id= เพื่อสร้างใบรับเงินใหม่
        if ($request->has('sale_id')) {
            $sale = Sale::with('customer')->find($request->query('sale_id'));

            if (! $sale) {
                return redirect()->route('receipts.create')->with('error', 'ไม่พบบิลที่ระบุ');
            }

            if ($sale->status !== 'completed') {
                return redirect()->route('receipts.create')
                    ->with('error', 'บิลนี้ไม่สามารถรับชำระเงินได้');
            }

            $idempotencyKey = (string) Str::uuid();
            return view('receipts.show', [
                'receipt'        => null,
                'sale'           => $sale,
                'idempotencyKey' => $idempotencyKey,
            ]);
        }

        // กรณีดู Receipt ที่มีอยู่
        if (! $receipt) {
            return redirect()->route('receipts.index')->with('error', 'ไม่พบใบรับเงิน');
        }

        $receipt->load(['sale.customer', 'sale.items.product', 'user', 'voidedBy']);
        return view('receipts.show', compact('receipt'));
    }

    public function destroy(Request $request, Receipt $receipt)
    {
        if ($receipt->status === 'voided') {
            return back()->with('error', 'ใบรับเงินนี้ถูกยกเลิกไปแล้ว');
        }

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            return DB::transaction(function () use ($receipt, $validated) {
                $sale    = Sale::lockForUpdate()->findOrFail($receipt->sale_id);
                $receipt = Receipt::lockForUpdate()->findOrFail($receipt->id);

                if ($receipt->status === 'voided') {
                    throw ValidationException::withMessages([
                        'receipt' => 'ใบรับเงินนี้ถูกยกเลิกไปแล้ว',
                    ]);
                }

                $total   = $this->toSatang($sale->total);
                $newPaid = max(0, $this->toSatang($sale->paid_amount) - $this->toSatang($receipt->amount));

                $sale->update([
                    'paid_amount'    => $this->fromSatang($newPaid),
                    'payment_status' => $newPaid <= 0 ? 'unpaid' : ($newPaid >= $total ? 'paid' : 'partial'),
                ]);

                $receipt->update([
                    'status'      => 'voided',
                    'voided_at'   => now(),
                    'voided_by'   => auth()->id(),
                    'void_reason' => $validated['void_reason'],
                ]);

                return redirect()->route('receipts.index')
                    ->with('success', 'ยกเลิกใบรับเงิน ' . $receipt->receipt_no . ' สำเร็จ');
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Receipt void failed: ' . $e->getMessage(), [
                'receipt_id' => $receipt->id,
                'user_id'    => auth()->id(),
            ]);
            return back()->with('error', 'ไม่สามารถยกเลิกได้: ' . $e->getMessage());
        }
    }


    public function createFromSale(Sale $sale) {
        if($sale->status !== 'completed') {
            return redirect()->route('receipts.create')->with('error', 'บิลนี้ไม่สามารถรับชำระเงินได้ (สถานะ: ' . $sale->status . ')');
        }
        // ตรวจสอบว่าจ่ายครบหรือยัง
        if($sale->payment_status === 'paid') {
            return redirect()->route('receipts.create')
            ->with('error', 'บิลนี้ชำระเงินครบแล้ว');
        }
        $sale->load('customer');
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();
        return view('receipts.show', [
            'receipt' => null,
            'sale' => $sale,
            'idempotencyKey' => $idempotencyKey,
        ]);
    }

    private function toSatang($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function fromSatang(int $satang): string
    {
        return number_format($satang / 100, 2, '.', '');
    }
}
