<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // วันที่รับของจริง (ต่างจาก order_date และ expected_date)
            $table->timestamp('received_at')->nullable()->after('expected_date');
            $table->index('received_at');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            // เครดิตเทอม (วัน) ของซัพพลายเออร์
            $table->unsignedSmallInteger('credit_term_days')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['received_at']);
            $table->dropColumn('received_at');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('credit_term_days');
        });
    }
};
