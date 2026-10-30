<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\ShiftSchedule;
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
        $todaySchedule = ShiftSchedule::where('user_id', auth()->id())
            ->where('shift_date', now()->toDateString())
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

        // 2. ตรวจสอบว่าวันนี้มีตารางกะหรือไม่
        $todaySchedule = ShiftSchedule::where('user_id', auth()->id())
            ->where('shift_date', now()->toDateString())
            ->first();

        if (!$todaySchedule) {
            return back()->with('error', 'วันนี้คุณไม่มีตารางกะ ไม่สามารถเปิดกะได้');
        }

        // 3. ตรวจสอบเวลากะ
        $currentTime = now()->format('H:i:s');
        if ($currentTime < $todaySchedule->start_time || $currentTime > $todaySchedule->end_time) {
            return back()->with('error',
                'ยังไม่ถึงเวลากะของคุณ (กะของคุณ: ' .
                \Carbon\Carbon::parse($todaySchedule->start_time)->format('H:i') . ' - ' .
                \Carbon\Carbon::parse($todaySchedule->end_time)->format('H:i') . ')'
            );
        }

        // 4. บันทึกกะใหม่
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'opening_note' => 'nullable|string|max:500',
        ]);

        CashierShift::create([
            'user_id'          => auth()->id(),
            'shift_start_time' => $todaySchedule->start_time,
            'shift_end_time'   => $todaySchedule->end_time,
            'opened_at'        => now(),
            'opening_cash'     => $validated['opening_cash'],
            'opening_note'     => $validated['opening_note'] ?? null,
            'status'           => 'open',
        ]);

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

        if ($cashierShift->status !== 'open') {
            return back()->with('error', 'กะนี้ถูกปิดไปแล้ว');
        }

        $validated = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'closing_note' => 'nullable|string|max:500',
        ]);

        // คำนวณยอดขายจาก Sales ในช่วงเวลากะ
        $sales = Sale::where('user_id', $cashierShift->user_id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$cashierShift->opened_at, now()])
            ->get();

        $cashSales     = $sales->where('payment_method', 'cash')->sum('total');
        $transferSales = $sales->where('payment_method', 'transfer')->sum('total');
        $cardSales     = $sales->where('payment_method', 'credit_card')->sum('total');
        $creditSales   = $sales->where('payment_method', 'credit')->sum('total'); // ✅ เพิ่ม
        $totalSales    = $sales->sum('total');

        // อัปเดตข้อมูลกะ
        $cashierShift->update([
            'closed_at'            => now(),
            'closing_cash'         => $validated['closing_cash'],
            'closing_note'         => $validated['closing_note'] ?? null,
            'total_cash_sales'     => $cashSales,
            'total_transfer_sales' => $transferSales,
            'total_card_sales'     => $cardSales,
            'total_credit_sales'   => $creditSales, // ✅ เพิ่ม
            'total_sales'          => $totalSales,
            'total_bills'          => $sales->count(),
            'status'               => 'closed',
        ]);

        return redirect()->route('cashier-shifts.show', $cashierShift->id)
            ->with('success', 'ปิดกะสำเร็จ!');
    }

    /**
     * แสดงสรุปการปิดกะ
     */
    public function show(CashierShift $cashierShift)
    {
        $cashierShift->load('user');

        // คำนวณ "เงินที่ควรมีในลิ้นชัก"
        $expectedCash = $cashierShift->opening_cash + $cashierShift->total_cash_sales - $cashierShift->total_expenses;
        $difference = $cashierShift->closing_cash - $expectedCash;

        return view('cashier-shifts.show', compact('cashierShift', 'expectedCash', 'difference'));
    }

    // ฟังก์ชันที่ไม่ได้ใช้ (บังคับตาม Resource)
    public function create() { return redirect()->route('cashier-shifts.index'); }
    public function edit(CashierShift $cashierShift) { return redirect()->route('cashier-shifts.index'); }
    public function update(Request $request, CashierShift $cashierShift) { return back(); }
    public function destroy(CashierShift $cashierShift) { return back(); }
}
