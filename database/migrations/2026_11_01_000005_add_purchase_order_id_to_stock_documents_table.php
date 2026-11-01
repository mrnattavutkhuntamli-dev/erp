<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ผูกใบรับเข้าสต็อกกับ PO ที่รับของ เพื่อให้ void ใบรับเข้าแล้วคืนสถานะ PO ได้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('customer_id')
                ->constrained('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_id');
        });
    }
};
