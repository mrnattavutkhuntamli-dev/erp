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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();                                      // เลขที่ใบเสร็จ
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // ลูกค้า
            $table->foreignId('user_id')->constrained();                                 // พนักงานขาย

            $table->decimal('subtotal', 12, 2);             // ยอดรวมก่อนหักส่วนลด
            $table->decimal('discount', 12, 2)->default(0); // ส่วนลด
            $table->decimal('total', 12, 2);                // ยอดสุทธิ

            $table->enum('payment_method', ['cash', 'transfer', 'credit_card']); // วิธีชำระ
            $table->decimal('received_amount', 12, 2)->default(0);               // เงินที่รับมา
            $table->decimal('change_amount', 12, 2)->default(0);                 // เงินทอน

            $table->enum('status', ['completed', 'voided'])->default('completed'); // สถานะ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
