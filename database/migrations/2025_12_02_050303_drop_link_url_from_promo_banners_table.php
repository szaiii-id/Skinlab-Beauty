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
        Schema::table('promo_banners', function (Blueprint $table) {
            // CEK DULU: Hanya hapus jika kolomnya benar-benar ada
            if (Schema::hasColumn('promo_banners', 'link_url')) {
                $table->dropColumn('link_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_banners', function (Blueprint $table) {
            // Saat rollback, kembalikan kolomnya jika belum ada
            if (!Schema::hasColumn('promo_banners', 'link_url')) {
                $table->string('link_url')->nullable();
            }
        });
    }
};