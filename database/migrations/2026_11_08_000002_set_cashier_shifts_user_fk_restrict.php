<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ห้ามลบผู้ใช้ที่มีประวัติกะ (เดิมเป็น cascade ทำให้ประวัติกะหายตาม)
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        $this->restoreOpenShiftGuard();
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $this->restoreOpenShiftGuard();
    }

    /**
     * SQLite: การแก้ FK ทำให้ Laravel สร้างตารางใหม่ และ unique index ของกะที่เปิดอยู่
     * (partial index: WHERE status = 'open') ถูกสร้างกลับมาเป็น unique ธรรมดาบน user_id
     * ผลคือผู้ใช้คนเดียวเปิดกะได้แค่ครั้งเดียวตลอดกาล จึงสร้าง partial index กลับให้ถูกต้อง
     * (MySQL ใช้ generated column ไม่ได้รับผลกระทบ)
     */
    private function restoreOpenShiftGuard(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS cashier_shifts_open_guard_unique');
        DB::statement(
            "CREATE UNIQUE INDEX cashier_shifts_open_guard_unique ON cashier_shifts (user_id) WHERE status = 'open'"
        );
    }
};
