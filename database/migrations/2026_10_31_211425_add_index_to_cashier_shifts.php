<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'cashier_shifts_user_status_index');
            $table->index(['status', 'opened_at'], 'cashier_shifts_status_opened_index');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropIndex('cashier_shifts_user_status_index');
            $table->dropIndex('cashier_shifts_status_opened_index');
        });
    }
};
