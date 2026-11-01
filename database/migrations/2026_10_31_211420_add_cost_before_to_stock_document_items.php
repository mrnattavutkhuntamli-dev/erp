<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_document_items', function (Blueprint $table) {
            $table->decimal('cost_before', 10, 2)->nullable()->after('stock_after');
            $table->decimal('cost_after', 10, 2)->nullable()->after('cost_before');
        });
    }

    public function down(): void
    {
        Schema::table('stock_document_items', function (Blueprint $table) {
            $table->dropColumn(['cost_before', 'cost_after']);
        });
    }
};
