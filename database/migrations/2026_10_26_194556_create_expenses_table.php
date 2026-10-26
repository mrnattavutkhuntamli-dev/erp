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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->date('expense_date');   // วันที่จ่าย
            $table->string('category', 50); // ประเภท: rent, utilities, salary, supplies, other
            $table->string('description');  // รายละเอียด

            $table->decimal('amount', 12, 2); // จำนวนเงิน

            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // ผู้บันทึก

            $table->timestamps();

            $table->index('expense_date');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
