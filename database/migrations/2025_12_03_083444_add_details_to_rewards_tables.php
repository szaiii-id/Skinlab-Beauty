<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Tabel Master 'rewards'
        Schema::table('rewards', function (Blueprint $table) {
            // HAPUS baris 'min_spend' karena sudah ada di tabel awal
            
            // Tambahkan kolom yang BELUM ada saja:
            $table->integer('validity_days')->default(30)->after('min_spend'); 
            $table->integer('max_per_user')->default(0)->after('stock'); 
            $table->boolean('is_claim_only')->default(false)->after('is_active'); 
            $table->string('image')->nullable()->after('description');
        });

        // 2. Update Tabel User 'user_rewards' (SNAPSHOT DATA)
        // Bagian ini TETAP, karena tabel user_rewards lama belum punya kolom ini
        Schema::table('user_rewards', function (Blueprint $table) {
            $table->string('type')->nullable()->after('code'); 
            $table->integer('value')->nullable()->after('type'); 
            $table->integer('min_spend')->default(0)->after('value'); 
            $table->string('source')->default('redeem')->after('reward_id'); 
        });
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            // Hapus kolom saat rollback
            $table->dropColumn(['validity_days', 'max_per_user', 'is_claim_only', 'image']);
        });

        Schema::table('user_rewards', function (Blueprint $table) {
            $table->dropColumn(['type', 'value', 'min_spend', 'source']);
        });
    }
};