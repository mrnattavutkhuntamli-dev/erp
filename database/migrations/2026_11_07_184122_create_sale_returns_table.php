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
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 50)->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();

            $table->decimal('total_value', 12, 2);               // มูลค่าสินค้าที่รับคืน
            $table->decimal('refund_amount', 12, 2)->default(0); // เงินที่คืนให้ลูกค้าจริง
            $table->string('refund_method', 20)->nullable();     // cash / transfer
            $table->string('reason', 500);

            $table->string('status', 20)->default('completed'); // completed / voided (เผื่อทำยกเลิกรอบหน้า)
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('void_reason', 500)->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->index('created_at');
        });


        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->restrictOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('value', 12, 2);                       // มูลค่าบรรทัดนี้ (หลังกระจายส่วนลดบิลแล้ว)
            $table->boolean('restock')->default(true);             // คืนเข้าสต็อกหรือไม่ (ของชำรุดไม่ควรเข้า)
            $table->integer('stock_after')->nullable();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('returned_amount', 12, 2)->default(0)->after('paid_amount'); // มูลค่ารับคืนสะสม
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('returned_amount'); // เงินที่คืนลูกค้าสะสม
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['returned_amount', 'refunded_amount']);
        });
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
