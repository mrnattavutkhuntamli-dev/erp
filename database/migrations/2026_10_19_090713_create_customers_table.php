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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();         // เบอร์โทร
            $table->string('email')->nullable();         // อีเมล
            $table->text('address')->nullable();         // ที่อยู่
            $table->string('license_plate')->nullable(); // ทะเบียนรถ (สำคัญมากสำหรับร้านอะไหล่)
            $table->boolean('is_active')->default(true); // สถานะ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
