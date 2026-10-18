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
        Schema::create('stock_documents', function (Blueprint $table) {
            $table->id();
            $table->string('doc_no')->unique(); // เลขที่ใบเอกสาร (เช่น IN-20231025-001)
            $table->enum('type', ['in', 'out']); // in = รับเข้า, out = เบิกออก
            $table->date('date'); // วันที่ทำรายการ
            $table->string('receiver_name')->nullable(); // ชื่อผู้รับ/ผู้ส่ง
            $table->text('note')->nullable(); // หมายเหตุ
            $table->foreignId('user_id')->constrained(); // พนักงานผู้ทำรายการ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_documents');
    }
};
