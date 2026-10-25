<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 50)->unique();                          // TMP-uuid ยาว 40 จึงเผื่อเป็น 50

            // ห้ามลบลูกค้า/ผู้ใช้ที่มีบิลอ้างอิงอยู่ (บิลเชื่อต้องรู้ตัวลูกหนี้เสมอ) ให้ปิดใช้งาน (is_active) แทนการลบ
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // ใช้ string ทั้งหมด (SQLite แก้ enum ยาก) และตรวจค่าที่ validation
            $table->string('payment_method', 20)->default('cash');               // cash / transfer / credit_card / credit
            $table->string('payment_status', 20)->default('unpaid');             // paid / unpaid / partial (default unpaid กันลืมตั้งค่าแล้วถูกนับว่าจ่ายแล้ว)
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();                                // ใช้กับขายเชื่อ

            $table->decimal('received_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);

            $table->string('status', 20)->default('completed');                  // completed / voided

            // ยกเลิกบิล: เก็บว่าใคร เมื่อไหร่ เพราะอะไร
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('void_reason', 500)->nullable();

            // กันกดบันทึกซ้ำ
            $table->uuid('idempotency_key')->nullable()->unique();

            $table->timestamps();

            $table->index('created_at');                                         // หน้าประวัติ / ยอดขายวันนี้
            $table->index(['status', 'payment_status']);                         // รายงาน / ลูกหนี้ค้างชำระ
            $table->index('due_date');                                           // ลูกหนี้เกินกำหนด
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
