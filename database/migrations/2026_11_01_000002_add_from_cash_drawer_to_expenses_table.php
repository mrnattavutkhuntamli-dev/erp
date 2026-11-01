<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ค่าใช้จ่ายที่จ่ายจากเงินในลิ้นชักแคชเชียร์ จะถูกหักตอนปิดกะ (expected cash)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('from_cash_drawer')->default(false)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('from_cash_drawer');
        });
    }
};
