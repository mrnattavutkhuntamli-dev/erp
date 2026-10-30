<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            // เงินสดที่รับชำระหนี้ (ใบรับเงิน) ระหว่างกะ — ต้องนับเป็นเงินในลิ้นชักด้วย
            $table->decimal('total_cash_receipts', 12, 2)->default(0)->after('total_credit_sales');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn('total_cash_receipts');
        });
    }
};
