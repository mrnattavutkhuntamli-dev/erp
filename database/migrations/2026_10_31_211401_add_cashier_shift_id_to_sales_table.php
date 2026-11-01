<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')
                ->nullable()
                ->after('user_id')
                ->constrained('cashier_shifts')
                ->nullOnDelete();

            $table->index('cashier_shift_id', 'sales_cashier_shift_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cashier_shift_id']);
            $table->dropIndex('sales_cashier_shift_id_index');
            $table->dropColumn('cashier_shift_id');
        });
    }
};
