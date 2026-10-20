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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_no')->unique(); // เช่น QT-20261020-0001
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained(); // พนักงานขาย

            $table->date('quote_date');  // วันที่ออกใบเสนอราคา
            $table->date('valid_until'); // ใช้ได้ถึงวันที่

            $table->decimal('subtotal', 12, 2);             // ยอดรวม
            $table->decimal('discount', 12, 2)->default(0); // ส่วนลด
            $table->decimal('total', 12, 2);                // ยอดสุทธิ

            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');

            $table->text('note')->nullable(); // หมายเหตุ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
