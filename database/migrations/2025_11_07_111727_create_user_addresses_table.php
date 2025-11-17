<?php
// [file name]: database/migrations/2024_01_15_000000_create_user_addresses_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('receiver_name');
            $table->string('phone_number', 20);
            $table->string('province_code', 2);
            $table->string('city_code', 4);
            $table->string('district_code', 7);
            $table->text('full_address');
            $table->string('postal_code', 10);
            $table->string('type')->default('home');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};