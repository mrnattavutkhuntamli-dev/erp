<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $fillable = [
        'user_id', 'shift_start_time', 'shift_end_time',
        'opened_at', 'opening_cash', 'opening_note',
        'closed_at', 'closing_cash', 'closing_note',
        'total_cash_sales', 'total_transfer_sales', 'total_card_sales',
        'total_credit_sales', 'total_cash_receipts', 'total_expenses', 'total_sales', 'total_bills', 'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

     public static function sellBlockReason(User $user): ?string
    {
        if (! config('pos.require_open_shift') || $user->hasRole('admin')) {
            return null;
        }

        $shift = static::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (! $shift) {
            return 'ยังไม่ได้เปิดกะ กรุณาเปิดกะก่อนทำการขาย (เมนู เปิด-ปิดกะ) ซึ่งเปิดได้เฉพาะช่วงเวลากะของคุณ';
        }

        if ($shift->shift_end_time) {
            $end = $shift->opened_at->copy()
                ->setTimeFromTimeString($shift->shift_end_time)
                ->addMinutes((int) config('pos.shift_grace_minutes', 0));

            if (now()->gt($end)) {
                return 'หมดเวลากะแล้ว (สิ้นสุด ' . substr($shift->shift_end_time, 0, 5) . ' น.) กรุณาปิดกะก่อน';
            }
        }

        return null;
    }
}
