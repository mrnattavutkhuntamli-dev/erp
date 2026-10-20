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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no')->unique();                                  // เลขที่ใบสั่งซื้อ เช่น PO-20261019-0001
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete(); // ซัพพลายเออร์
            $table->foreignId('user_id')->constrained();                        // ผู้สร้าง PO

            $table->date('order_date');                // วันที่สั่งซื้อ
            $table->date('expected_date')->nullable(); // วันที่คาดว่าจะได้รับของ

            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');

            $table->decimal('total_amount', 12, 2)->default(0); // ยอดรวม

            $table->text('note')->nullable(); // หมายเหตุ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
