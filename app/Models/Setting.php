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
        return cache()->rememberForever('setting.' . $key, function () use ($key, $default) {
            $setting = self::where('key', $key)->where('is_active', true)->first();
            return $setting ? $setting->value : $default;
        });
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
