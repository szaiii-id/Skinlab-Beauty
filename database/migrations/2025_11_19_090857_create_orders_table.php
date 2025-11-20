<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Relasi User
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // ✅ PERBAIKAN DISINI: Gunakan 'user_addresses'
            // Karena nama tabel Anda adalah user_addresses, bukan addresses
            $table->foreignId('shipping_address_id')
                  ->nullable()
                  ->constrained('user_addresses') // <--- Ganti 'addresses' jadi 'user_addresses'
                  ->nullOnDelete();

            // Identitas Order
            $table->string('order_number')->unique(); 
            
            // Keuangan
            $table->decimal('subtotal', 15, 2);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            
            // Integrasi Midtrans
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->default('online');
            $table->string('snap_token')->nullable();
            
            // Status Pengiriman
            $table->string('order_status')->default('pending');
            $table->string('resi_number')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};