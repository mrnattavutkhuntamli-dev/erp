<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * กัน 1 ผู้ใช้เปิดกะซ้อนกัน (status = 'open' ได้แค่ 1 แถวต่อผู้ใช้)
     *  - MySQL/MariaDB: generated column + unique index
     *  - SQLite (ใช้ในเทสต์): partial unique index (SQLite เพิ่ม STORED column ด้วย ALTER ไม่ได้)
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(
                "CREATE UNIQUE INDEX cashier_shifts_open_guard_unique ON cashier_shifts (user_id) WHERE status = 'open'"
            );

            return;
        }

        DB::statement("
            ALTER TABLE cashier_shifts
            ADD COLUMN open_guard BIGINT
            GENERATED ALWAYS AS (
                CASE WHEN status = 'open' THEN user_id ELSE NULL END
            ) STORED
        ");
        DB::statement("
            CREATE UNIQUE INDEX cashier_shifts_open_guard_unique
            ON cashier_shifts (open_guard)
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS cashier_shifts_open_guard_unique');

            return;
        }

        DB::statement("DROP INDEX cashier_shifts_open_guard_unique ON cashier_shifts");
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn('open_guard');
        });
    }
};
