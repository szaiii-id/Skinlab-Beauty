<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skincare_routine_id')->constrained()->cascadeOnDelete();
            $table->string('year_month', 7)->index();
            $table->json('completed_days')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'skincare_routine_id', 'year_month'], 'unique_monthly_log');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_completions');
    }
};