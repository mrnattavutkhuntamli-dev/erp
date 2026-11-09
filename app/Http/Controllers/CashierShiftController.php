<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\ShiftSchedule;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierShiftController extends Controller
{
    /**
     * แสดงหน้าระบบเปิด-ปิดกะ
     */
    public function index()
    {
        // ตรวจสอบว่าพนักงานคนนี้มีกะที่เปิดอยู่หรือไม่
        $currentShift = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest()
            ->first();

        // ดึงตารางกะของวันนี้ (ถ้ามี)
        $todaySchedule = ShiftSchedule::findActive(auth()->id(), now())
            ?? ShiftSchedule::where('user_id', auth()->id())
                ->where('shift_date', now()->toDateString())
                ->orderBy('start_time')
                ->first();

        // ดึงประวัติกะ (เฉพาะของพนักงานคนนี้ ถ้าไม่ใช่ Admin)
        $shiftsQuery = CashierShift::with('user')->latest();
        if (!auth()->user()->hasRole('admin')) {
            $shiftsQuery->where('user_id', auth()->id());
        }
        $shifts = $shiftsQuery->paginate(15);

        return view('cashier-shifts.index', compact('currentShift', 'todaySchedule', 'shifts'));
    }

    /**
     * เปิดกะใหม่
     */
    public function store(Request $request)
    {
        // 1. ตรวจสอบว่ามีกะเปิดอยู่แล้วหรือไม่
        $existing = CashierShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();

        if ($existing) {
            return back()->with('error', 'คุณมีกะที่เปิดอยู่แล้ว กรุณาปิดกะก่อน');
        }

        // 2-3. หากะของวันนี้ที่ครอบคลุมเวลาปัจจุบัน (รองรับหลายกะต่อวัน)
        // (รองรับกะข้ามคืน เช่น 22:00–06:00 รวมถึงตอนหลังเที่ยงคืนของกะที่เริ่มเมื่อวาน)
        $todaySchedule = ShiftSchedule::findActive(auth()->id(), now());

        if (! $todaySchedule) {
            return back()->with('error', 'ขณะนี้ไม่อยู่ในช่วงเวลากะที่กำหนดไว้ ไม่สามารถเปิดกะได้');
        }

        // 4. บันทึกกะใหม่
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'opening_note' => 'nullable|string|max:500',
        ]);

        // เช็กซ้ำอีกครั้งหลัง validate (กันกดซ้ำ)
        if (CashierShift::where('user_id', auth()->id())->where('status', 'open')->exists()) {
            return back()->with('error', 'คุณมีกะที่เปิดอยู่แล้ว กรุณาปิดกะก่อน');
        }

        try {
            CashierShift::create([
                'user_id'          => auth()->id(),
                'shift_start_time' => $todaySchedule->start_time,
                'shift_end_time'   => $todaySchedule->end_time,
                'opened_at'        => now(),
                'opening_cash'     => $validated['opening_cash'],
                'opening_note'     => $validated['opening_note'] ?? null,
                'status'           => 'open',
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // กดเปิดกะซ้ำพร้อมกัน → unique index กันไว้ที่ DB
            return back()->with('error', 'คุณมีกะที่เปิดอยู่แล้ว กรุณาปิดกะก่อน');
        }

        return redirect()->route('cashier-shifts.index')
            ->with('success', 'เปิดกะสำเร็จ! เงินทอนเริ่มต้น ฿' . number_format($validated['opening_cash'], 2));
    }

    /**
     * ปิดกะ (คำนวณยอดขาย + บันทึกเงินนับได้)
     */
    public function close(Request $request, CashierShift $cashierShift)
    {
        // ตรวจสอบสิทธิ์: ต้องเป็นเจ้าของกะ หรือ Admin
        if ($cashierShift->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return back()->with('error', 'คุณไม่มีสิทธิ์ปิดกะนี้');
        }

        $validated = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'closing_note' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($cashierShift, $validated) {
        $cashierShift = CashierShift::lockForUpdate()->findOrFail($cashierShift->id);

        if ($cashierShift->status !== 'open') {
            return back()->with('error', 'กะนี้ถูกปิดไปแล้ว');
        }

        $closeAt = now();
        $totals  = $this->computeTotals($cashierShift, $closeAt);

        // อัปเดตข้อมูลกะ
        $cashierShift->update($totals + [
            'closed_at'    => $closeAt,
            'closing_cash' => $validated['closing_cash'],
            'closing_note' => $validated['closing_note'] ?? null,
            'status'       => 'closed',
        ]);

        return redirect()->route('cashier-shifts.show', $cashierShift->id)
            ->with('success', 'ปิดกะสำเร็จ!');
        });
    }

    /**
     * คำนวณยอดสรุปของกะ ณ เวลา $closeAt (ใช้ร่วมกันทั้งตอนปิดกะและตอนดูกะที่ยังเปิดอยู่)
     */
    private function computeTotals(CashierShift $cashierShift, $closeAt): array
    {
        // คำนวณยอดขายจาก Sales ในช่วงเวลากะ
        // ผูกตาม cashier_shift_id เป็นหลัก; บิลเก่าที่ไม่ได้ผูกกะ (null) ใช้ช่วงเวลา+ผู้ใช้แทน
        $sales = Sale::where('status', 'completed')
            ->where(function ($q) use ($cashierShift, $closeAt) {
                $q->where('cashier_shift_id', $cashierShift->id)
                  ->orWhere(function ($q2) use ($cashierShift, $closeAt) {
                      $q2->whereNull('cashier_shift_id')
                         ->where('user_id', $cashierShift->user_id)
                         ->whereBetween('created_at', [$cashierShift->opened_at, $closeAt]);
                  });
            })
            ->get();

        $cashSales     = $sales->where('payment_method', 'cash')->sum('total');
        $transferSales = $sales->where('payment_method', 'transfer')->sum('total');
        $cardSales     = $sales->where('payment_method', 'credit_card')->sum('total');
        $creditSales   = $sales->where('payment_method', 'credit')->sum('total'); // ✅ เพิ่ม
        $totalSales    = $sales->sum('total');

        // เงินสดที่รับชำระหนี้ (ใบรับเงิน) ที่พนักงานคนนี้รับระหว่างกะ
        $cashReceipts = Receipt::where('user_id', $cashierShift->user_id)
            ->where('status', 'active')
            ->where('payment_method', 'cash')
            ->whereBetween('created_at', [$cashierShift->opened_at, $closeAt])
            ->sum('amount');

        // ค่าใช้จ่ายที่จ่ายจากเงินในลิ้นชักระหว่างกะ (ติ๊ก "จ่ายจากลิ้นชัก" ตอนบันทึกค่าใช้จ่าย)
        // (ฝ่ายบัญชี/admin เป็นคนบันทึกได้ จึงผูกกับกะผ่าน cashier_shift_id; รายการเก่าที่ไม่ผูกใช้ช่วงเวลา+ผู้ใช้แทน)
        $totalExpenses = Expense::where('from_cash_drawer', true)
            ->where(function ($q) use ($cashierShift, $closeAt) {
                $q->where('cashier_shift_id', $cashierShift->id)
                  ->orWhere(function ($q2) use ($cashierShift, $closeAt) {
                      $q2->whereNull('cashier_shift_id')
                         ->where('user_id', $cashierShift->user_id)
                         ->whereBetween('created_at', [$cashierShift->opened_at, $closeAt]);
                  });
            })
            ->sum('amount');

        // เงินสดที่คืนให้ลูกค้า (ใบรับคืนสินค้าที่คืนเป็นเงินสด) ระหว่างกะ — ต้องหักออกจากเงินในลิ้นชัก
        $cashRefunds = SaleReturn::where('status', 'completed')
            ->where('refund_method', 'cash')
            ->where(function ($q) use ($cashierShift, $closeAt) {
                $q->where('cashier_shift_id', $cashierShift->id)
                  ->orWhere(function ($q2) use ($cashierShift, $closeAt) {
                      $q2->whereNull('cashier_shift_id')
                         ->where('user_id', $cashierShift->user_id)
                         ->whereBetween('created_at', [$cashierShift->opened_at, $closeAt]);
                  });
            })
            ->sum('refund_amount');

        return [
            'total_cash_sales'     => $cashSales,
            'total_transfer_sales' => $transferSales,
            'total_card_sales'     => $cardSales,
            'total_credit_sales'   => $creditSales,
            'total_cash_receipts'  => $cashReceipts,
            'total_cash_refunds'   => $cashRefunds,
            'total_expenses'       => $totalExpenses,
            'total_sales'          => $totalSales,
            'total_bills'          => $sales->count(),
        ];
    }

    /**
     * แสดงสรุปการปิดกะ
     */
    public function show(CashierShift $cashierShift)
    {
        if ($cashierShift->user_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            abort(403, 'คุณไม่มีสิทธิ์ดูกะนี้');
        }

        $cashierShift->load('user');

        // กะที่ยังเปิดอยู่: คำนวณยอดสดจนถึงตอนนี้ (แสดงผลอย่างเดียว ไม่บันทึกลง DB)
        if ($cashierShift->status === 'open') {
            $cashierShift->forceFill($this->computeTotals($cashierShift, now()));
        }

        // คำนวณ "เงินที่ควรมีในลิ้นชัก"
        $expectedCash = $cashierShift->opening_cash + $cashierShift->total_cash_sales + $cashierShift->total_cash_receipts - $cashierShift->total_cash_refunds - $cashierShift->total_expenses;
        $difference = $cashierShift->closing_cash - $expectedCash;

        return view('cashier-shifts.show', compact('cashierShift', 'expectedCash', 'difference'));
    }

    // ฟังก์ชันที่ไม่ได้ใช้ (บังคับตาม Resource)
    public function create() { return redirect()->route('cashier-shifts.index'); }
    public function edit(CashierShift $cashierShift) { return redirect()->route('cashier-shifts.index'); }
    public function update(Request $request, CashierShift $cashierShift) { return back(); }
    public function destroy(CashierShift $cashierShift) { return back(); }
}
