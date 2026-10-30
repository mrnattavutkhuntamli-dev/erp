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
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 1. ประกาศคอลัมน์ทั้งหมดก่อน
            $table->date('shift_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('note')->nullable();
            $table->timestamps();

            // 2. สร้าง Index ทั่วไป
            $table->index(['user_id', 'shift_date']);

            // 3. ✅ สร้าง Unique Index ไว้ท้ายสุด
            $table->unique(['user_id', 'shift_date', 'start_time'], 'unique_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_schedules');
    }
};
