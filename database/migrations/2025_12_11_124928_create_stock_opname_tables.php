<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel History (Untuk mencatat riwayat keluar/masuk)
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('type'); // 'sale', 'restock', 'opname'
            $table->integer('qty_change'); // -5 atau +10
            $table->integer('current_stock'); // Stok akhir
            $table->string('note')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable(); // ID Opname atau Order
            $table->foreignId('user_id')->nullable(); // Siapa adminnya
            $table->timestamps();
        });

        // 2. Tabel Header Opname (Sesi Opname)
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number')->unique(); // Kode unik: SO-001
            $table->date('opname_date');
            $table->string('status')->default('draft'); // draft, processing, completed
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable(); // Admin ID
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Item Opname (Lembar Kerja Hitungan)
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            // Relasi ke tabel Anda:
            $table->foreignId('product_variant_id')->constrained('product_variants'); 
            
            $table->integer('system_qty'); // Stok komputer saat opname dimulai (Snapshot)
            $table->integer('physical_qty')->nullable(); // Stok fisik (Inputan user)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_histories');
    }
};