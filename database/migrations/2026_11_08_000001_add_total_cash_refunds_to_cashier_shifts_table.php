<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            // เงินสดที่คืนให้ลูกค้าจากการรับคืนสินค้าระหว่างกะ (หักจากเงินในลิ้นชัก)
            $table->decimal('total_cash_refunds', 12, 2)->default(0)->after('total_cash_receipts');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn('total_cash_refunds');
        });
    }
};
