<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'shift_date', 'start_time', 'end_time', 'note'];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** เวลาเริ่มกะ (วันที่ + เวลา) */
    public function startsAt(): Carbon
    {
        return Carbon::parse($this->shift_date->toDateString() . ' ' . $this->start_time);
    }

    /** เวลาสิ้นสุดกะ — ถ้าเวลาจบ <= เวลาเริ่ม ถือเป็นกะข้ามคืน (จบวันถัดไป) */
    public function endsAt(): Carbon
    {
        return static::resolveEnd($this->shift_date->toDateString(), $this->start_time, $this->end_time);
    }

    public function isOvernight(): bool
    {
        return $this->endsAt()->toDateString() !== $this->startsAt()->toDateString();
    }

    /**
     * หากะของพนักงานที่ครอบคลุมเวลา $at (รองรับกะข้ามคืนที่เริ่มเมื่อวาน)
     */
    public static function findActive(int $userId, Carbon $at): ?self
    {
        $candidates = static::where('user_id', $userId)
            ->whereDate('shift_date', '>=', $at->copy()->subDay()->toDateString())
            ->whereDate('shift_date', '<=', $at->toDateString())
            ->get();

        return $candidates->first(
            fn (self $s) => $s->startsAt()->lte($at) && $s->endsAt()->gte($at)
        );
    }

    /**
     * ช่วงเวลาที่ขอ ซ้อนทับกับกะอื่นของพนักงานคนเดียวกันหรือไม่ (รองรับกะข้ามคืน)
     */
    public static function overlaps(int $userId, string $date, string $start, string $end, ?int $ignoreId = null): bool
    {
        $newStart = Carbon::parse($date . ' ' . $start);
        $newEnd   = static::resolveEnd($date, $start, $end);

        $others = static::where('user_id', $userId)
            ->whereDate('shift_date', '>=', Carbon::parse($date)->subDay()->toDateString())
            ->whereDate('shift_date', '<=', Carbon::parse($date)->addDay()->toDateString())
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        return $others->contains(
            fn (self $s) => $newStart->lt($s->endsAt()) && $s->startsAt()->lt($newEnd)
        );
    }

    public static function resolveEnd(string $date, string $start, string $end): Carbon
    {
        $startAt = Carbon::parse($date . ' ' . $start);
        $endAt   = Carbon::parse($date . ' ' . $end);

        return $endAt->lte($startAt) ? $endAt->addDay() : $endAt;
    }
}
