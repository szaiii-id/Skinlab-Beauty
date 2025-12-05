<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ban_requests', function (Blueprint $table) {
            $table->id();
            
            // User yang diminta di-ban (Hapus request jika user dihapus permanen)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Admin yang melakukan request
            $table->foreignId('requested_by')->constrained('admins');
            
            // Data request
            $table->string('reason', 50); // return_abuse, fraud, etc
            $table->text('description');
            $table->json('evidence')->nullable(); // Menampung bukti (array/json)
            
            // Status dan approval
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Reviewer (Super Admin)
            $table->foreignId('reviewed_by')->nullable()->constrained('admins');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            
            // Kita TIDAK menggunakan softDeletes() agar sesuai dengan Model BanRequest terakhir
            
            // Indexes untuk performa query
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ban_requests');
    }
};