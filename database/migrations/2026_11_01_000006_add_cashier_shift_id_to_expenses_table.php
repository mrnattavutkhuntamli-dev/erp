<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // กะแคชเชียร์ที่ค่าใช้จ่ายนี้ "จ่ายจากลิ้นชัก" (คนบันทึกอาจเป็นฝ่ายบัญชี ไม่ใช่เจ้าของกะ)
            $table->foreignId('cashier_shift_id')
                ->nullable()
                ->after('user_id')
                ->constrained('cashier_shifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_shift_id');
        });
    }
};
