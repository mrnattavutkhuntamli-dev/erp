<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();

            // ✅ 1. ใช้ unique ก่อน แต่ระวัง: ตอน Voided จะเปลี่ยนเป็น 'VOIDED-xxx' ซึ่งยัง unique อยู่
            $table->string('receipt_no', 60)->unique();

            // ✅ 2. ใช้ restrictOnDelete ถูกต้อง (ห้ามลบบิลที่มีใบรับเงิน)
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->date('payment_date')->index();
            $table->decimal('amount', 12, 2);

            // ✅ 3. เปลี่ยน payment_method เป็น enum (Database ตรวจสอบให้เลย)
            $table->enum('payment_method', ['cash', 'transfer', 'credit_card']);

            $table->text('note')->nullable();

            // ✅ 4. เปลี่ยน status เป็น enum (Database ตรวจสอบให้เลย)
            $table->enum('status', ['active', 'voided'])->default('active');

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('void_reason')->nullable(); // ✅ 5. เปลี่ยนจาก string เป็น text (เผื่อเขียนยาว)

            // ✅ 6. idempotency_key ควรเป็น 255 (UUID 36 ตัว)
            $table->uuid('idempotency_key')->nullable()->unique();

            $table->timestamps();

            // ✅ 7. Composite index
            $table->index(['sale_id', 'status']);
            $table->index(['payment_date', 'status']); // ✅ 8. เพิ่ม index สำหรับรายงาน
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
