<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ซ่อมฐานข้อมูลที่รัน migration 2026_10_26_193009 เวอร์ชันเก่า (ที่ลบคอลัมน์ทิ้ง) ไปแล้ว
     * ถ้ามีคอลัมน์อยู่แล้วจะไม่ทำอะไร จึงรันซ้ำได้ปลอดภัย
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sale_items', 'stock_after')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->integer('stock_after')->nullable()->after('total');
            });
        }
    }

    public function down(): void
    {
        // ไม่ลบกลับ: คอลัมน์นี้เป็นของ migration 2026_10_26_193009
    }
};
