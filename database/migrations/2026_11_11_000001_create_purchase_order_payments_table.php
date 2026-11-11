<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * บันทึกประวัติการจ่ายเงินให้ Supplier แยกเป็นรายการ เพื่อให้ยกเลิก (void) รายการที่จ่ายผิดได้
 * (เดิมมีแค่ purchase_orders.paid_amount ที่บวกเพิ่มอย่างเดียว แก้ไม่ได้)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('active'); // active | voided
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_order_id', 'status']);
        });

        // ยอดที่จ่ายไปแล้วก่อนมีตารางนี้ → สร้างเป็นรายการเดียวต่อ PO เพื่อให้ void ได้
        $now = now();
        DB::table('purchase_orders')->where('paid_amount', '>', 0)->orderBy('id')->each(function ($po) use ($now) {
            DB::table('purchase_order_payments')->insert([
                'purchase_order_id' => $po->id,
                'user_id'           => null,
                'amount'            => $po->paid_amount,
                'status'            => 'active',
                'created_at'        => $po->received_at ?? $now,
                'updated_at'        => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_payments');
    }
};
