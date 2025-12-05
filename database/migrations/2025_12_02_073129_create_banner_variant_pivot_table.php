<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jika tabel lama (product_promo_banner) ada, drop dulu biar bersih
        Schema::dropIfExists('product_promo_banner');

        Schema::create('product_promo_banner', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Banner
            $table->foreignId('promo_banner_id')->constrained()->cascadeOnDelete();
            
            // Relasi ke VARIAN (Bukan Product lagi)
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            
            // Data Diskon Unik per Varian
            $table->string('discount_type')->default('percent'); // 'percent' or 'fixed'
            $table->integer('discount_value')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_promo_banner');
    }
};