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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            // เลขที่เอกสาร เช่น ADJ-20261020-0001
            $table->string('adj_no')->unique();

            // ความสัมพันธ์กับสินค้าและพนักงาน
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // พนักงานผู้ทำรายการ

            // ประเภทการปรับปรุง: addition = เพิ่ม, subtraction = ลด
            $table->enum('type', ['addition', 'subtraction']);

            // จำนวนที่ปรับ
            $table->integer('quantity');

            // สต็อกก่อนและหลังปรับ (เก็บ Snapshot ไว้ดูย้อนหลัง)
            $table->integer('stock_before');
            $table->integer('stock_after');

            // เหตุผลการปรับ
            $table->enum('reason', ['damaged', 'lost', 'stocktake', 'return', 'other']);

            // หมายเหตุเพิ่มเติม
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
