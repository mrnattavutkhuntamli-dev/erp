<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - กันกดบันทึกซ้ำ (idempotency key) ให้ ปรับปรุงสต็อก / PO / ใบเสนอราคา / ค่าใช้จ่าย
 * - ใบรับ/ใบเบิกสินค้า: เก็บ เวลา/ผู้ยกเลิก/เหตุผล (เดิมเก็บแค่ status)
 * - รายการจ่ายเงิน PO: เก็บ วันที่จ่าย / วิธีจ่าย / หมายเหตุ
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['stock_adjustments', 'purchase_orders', 'quotations', 'expenses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->unique();
            });
        }

        Schema::table('stock_documents', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('void_reason', 500)->nullable();
        });

        Schema::table('purchase_order_payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 20)->nullable(); // cash | transfer | cheque | other
            $table->string('note', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_date', 'payment_method', 'note']);
        });

        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });

        foreach (['stock_adjustments', 'purchase_orders', 'quotations', 'expenses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropUnique($tableName . '_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
