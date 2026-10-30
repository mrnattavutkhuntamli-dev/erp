<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->time('shift_start_time')->nullable();
            $table->time('shift_end_time')->nullable();

            // --- ข้อมูลตอนเปิดกะ ---
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->text('opening_note')->nullable();

            // --- ข้อมูลตอนปิดกะ ---
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->text('closing_note')->nullable();

            // --- Snapshot ยอดขาย ---
            $table->decimal('total_cash_sales', 12, 2)->default(0);
            $table->decimal('total_transfer_sales', 12, 2)->default(0);
            $table->decimal('total_card_sales', 12, 2)->default(0);
            $table->decimal('total_credit_sales', 12, 2)->default(0);
            $table->decimal('total_expenses', 12, 2)->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->integer('total_bills')->default(0);

            // สถานะ
            $table->enum('status', ['open', 'closed'])->default('open');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
