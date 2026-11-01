<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม sale_items.stock_after (ยอดสต็อกคงเหลือหลังขายรายการนี้)
     * โค้ดฝั่งขาย/ใบเสนอราคา/รายงานความเคลื่อนไหวสต็อกใช้คอลัมน์นี้
     * (เดิม up() สั่งลบคอลัมน์ ซึ่งสลับกับชื่อไฟล์ ทำให้ DB ใหม่ขายของไม่ได้)
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
        if (Schema::hasColumn('sale_items', 'stock_after')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('stock_after');
            });
        }
    }
};
