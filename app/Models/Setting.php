<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ดึงค่าจาก Database (เฉพาะที่ Active)
    public static function get($key, $default = null)
    {
        // แคชเฉพาะค่าจาก DB (ห่อใน array เพื่อแยก "ไม่มีค่า" ออกจาก null)
        // ไม่แคชค่า default เพื่อไม่ให้ default ของผู้เรียกคนแรกค้างไว้ถาวร
        $cached = cache()->rememberForever('setting.' . $key, function () use ($key) {
            $setting = self::where('key', $key)->where('is_active', true)->first();

            return ['found' => (bool) $setting, 'value' => $setting?->value];
        });

        if (! is_array($cached)) {
            // แคชรูปแบบเก่า (ก่อนแก้ไข) -> ล้างแล้วอ่านใหม่
            cache()->forget('setting.' . $key);

            return self::get($key, $default);
        }

        return $cached['found'] ? $cached['value'] : $default;
    }

    // บันทึกค่า
    public static function set($key, $value)
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'is_active' => true]
        );
        cache()->forget('setting.' . $key);
    }
}
