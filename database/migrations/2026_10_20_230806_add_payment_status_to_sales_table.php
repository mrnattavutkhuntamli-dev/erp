<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // สถานะการชำระเงิน
            $table->enum('payment_status', ['paid', 'unpaid', 'partial'])
                ->default('paid')
                ->after('payment_method');

            // ยอดที่ชำระแล้ว (สำหรับกรณีผ่อน/จ่ายบางส่วน)
            $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_status');

            // วันครบกำหนดชำระ (สำหรับขายเชื่อ)
            $table->date('due_date')->nullable()->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_amount', 'due_date']);
        });
    }
};
