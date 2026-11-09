<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * กะที่ปิดไปแล้วก่อนแก้บัค (total_cash_refunds ไม่ถูกบันทึกตอนปิดกะ) → คำนวณยอดคืนเงินสดย้อนหลังให้
     * ใช้เงื่อนไขเดียวกับ CashierShiftController::computeTotals()
     */
    public function up(): void
    {
        DB::table('cashier_shifts')
            ->where('status', 'closed')
            ->whereNotNull('closed_at')
            ->orderBy('id')
            ->each(function ($shift) {
                $sum = DB::table('sale_returns')
                    ->where('status', 'completed')
                    ->where('refund_method', 'cash')
                    ->where(function ($q) use ($shift) {
                        $q->where('cashier_shift_id', $shift->id)
                          ->orWhere(function ($q2) use ($shift) {
                              $q2->whereNull('cashier_shift_id')
                                 ->where('user_id', $shift->user_id)
                                 ->whereBetween('created_at', [$shift->opened_at, $shift->closed_at]);
                          });
                    })
                    ->sum('refund_amount');

                if ((float) $sum > 0) {
                    DB::table('cashier_shifts')
                        ->where('id', $shift->id)
                        ->update(['total_cash_refunds' => $sum]);
                }
            });
    }

    public function down(): void
    {
        // ไม่ย้อนข้อมูล (ค่าที่คำนวณถูกต้องอยู่แล้ว)
    }
};
