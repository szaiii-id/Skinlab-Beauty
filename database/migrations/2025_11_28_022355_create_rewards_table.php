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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Voucher 10rb"
            $table->text('description')->nullable();
            $table->integer('points_required'); // Harga: 1000
            $table->string('type'); // 'fixed_discount', 'percent', 'free_shipping'
            $table->integer('value')->nullable(); // Nilai: 10000
            $table->integer('min_spend')->default(0); // Syarat belanja min
            $table->integer('stock')->default(999);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
