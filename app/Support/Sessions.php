<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sessions
{
    /**
     * ล้าง session อื่นของผู้ใช้ (เช่น หลังเปลี่ยนรหัสผ่าน/role) ยกเว้น session ที่ระบุ
     * ทำงานเมื่อใช้ SESSION_DRIVER=database เท่านั้น (driver อื่นไม่มีตารางให้ลบ)
     */
    public static function forgetFor(int $userId, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $q = DB::table(config('session.table', 'sessions'))->where('user_id', $userId);

        if ($exceptSessionId) {
            $q->where('id', '!=', $exceptSessionId);
        }

        $q->delete();
    }
}
