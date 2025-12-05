<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // 1. Tambahkan kolom Snapshot Varian (Boleh null dulu utk data lama)
            $table->string('variant_name')->nullable()->after('product_name');

            // 2. UBAH FOREIGN KEY (Agar aman jika master dihapus)
            // A. Hapus kunci tamu yang lama (format default: table_column_foreign)
            $table->dropForeign(['product_variant_id']);

            // B. Ubah kolom agar menerima NULL
            $table->unsignedBigInteger('product_variant_id')->nullable()->change();

            // C. Pasang kunci tamu baru dengan aturan "Set Null jika dihapus"
            $table->foreign('product_variant_id')
                  ->references('id')
                  ->on('product_variants')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Kembalikan ke pengaturan awal (Hati-hati, ini bisa error jika ada data null)
            $table->dropColumn('variant_name');
            
            $table->dropForeign(['product_variant_id']);
            $table->unsignedBigInteger('product_variant_id')->nullable(false)->change();
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
        });
    }
};