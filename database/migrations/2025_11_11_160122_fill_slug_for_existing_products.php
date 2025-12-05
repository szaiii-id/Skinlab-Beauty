<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Tambahkan ini
use Illuminate\Support\Str;        // Tambahkan ini

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PENTING: Gunakan DB::table, JANGAN gunakan Model Product::all()
        // Ini untuk menghindari error SoftDeletes (deleted_at) jika kolom belum ada
        
        $products = DB::table('products')->get();

        foreach ($products as $product) {
            // Cek jika slug kosong, baru diisi
            if (empty($product->slug)) {
                $slug = Str::slug($product->name);
                
                // Update menggunakan Query Builder langsung
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['slug' => $slug]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Biasanya tidak perlu melakukan apa-apa saat rollback untuk pengisian data
    }
};