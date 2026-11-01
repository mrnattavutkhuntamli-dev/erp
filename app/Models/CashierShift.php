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

    /**
     * เวลาสิ้นสุดกะตามตาราง (วันที่ + เวลา) รองรับกะข้ามคืน
     *  - กะปกติ: จบวันเดียวกับที่เปิดกะ
     *  - กะข้ามคืน (เวลาจบ <= เวลาเริ่ม): ถ้าเปิดกะก่อนเที่ยงคืน จบวันถัดไป / ถ้าเปิดหลังเที่ยงคืน จบวันเดียวกัน
     */
    public function scheduledEnd(): ?\Carbon\Carbon
    {
        if (! $this->shift_end_time) {
            return null;
        }

        $end = $this->opened_at->copy()->setTimeFromTimeString($this->shift_end_time);

        if ($this->shift_start_time && $this->shift_end_time <= $this->shift_start_time) {
            $openedTime = $this->opened_at->format('H:i:s');
            if ($openedTime >= $this->shift_start_time) {
                $end->addDay();
            }
        }

        return $end;
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

        if ($end = $shift->scheduledEnd()) {
            $end = $end->addMinutes((int) config('pos.shift_grace_minutes', 0));

            if (now()->gt($end)) {
                return 'หมดเวลากะแล้ว (สิ้นสุด ' . substr($shift->shift_end_time, 0, 5) . ' น.) กรุณาปิดกะก่อน';
            }
        }

        return null;
    }

    /**
     * กะที่ "ปิดแล้ว" ของผู้ใช้ ซึ่งครอบคลุมเวลา $at (ใช้กันแก้ไข/ยกเลิกรายการเงินสดย้อนหลังหลังปิดกะ)
     */
    public static function closedCovering(int $userId, \Carbon\Carbon $at): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', 'closed')
            ->where('opened_at', '<=', $at)
            ->where('closed_at', '>=', $at)
            ->first();
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed' || $this->closed_at !== null;
    }

    public static function currentFor(User $user): ?self
    {
        return static::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

}
