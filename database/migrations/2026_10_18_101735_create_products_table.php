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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete(); //รหัสผู้จัดจำหน่าย
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // หมวดหมู่ id
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete(); // จำนวนหน่วย นับเป็นสินค้านี้เป็นอะไร


            $table->string('sku')->unique(); // รหัสสินค้า
            $table->string('name'); //ชื่อสินค้า
            $table->text('description')->nullable(); // รายละเอียด (เผื่อไว้)


            $table->decimal('cost', 10, 2)->default(0); // ราคาต้นทุน (ERP ควรมี)
            $table->decimal('price',10,2); // ราคาขาย

            $table->integer('view_count')->default(0); // จำนวนการกดดู
            $table->integer('view_buy')->default(0); // จำนวนการชื้อสินค้าชิ้นนี้
            $table->integer('stock')->default(0);  // จำนวนสต็อก
            $table->boolean('is_active')->default(true); //สถานะการแสดง
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
