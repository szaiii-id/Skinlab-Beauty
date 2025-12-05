<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cek kolom satu per satu untuk menghindari error "Column already exists"
            
            if (!Schema::hasColumn('users', 'is_banned')) {
                $table->boolean('is_banned')->default(false)->after('password');
            }
            
            if (!Schema::hasColumn('users', 'banned_at')) {
                $table->timestamp('banned_at')->nullable()->after('is_banned');
            }
            
            if (!Schema::hasColumn('users', 'ban_reason')) {
                $table->string('ban_reason', 255)->nullable()->after('banned_at');
            }
            
            if (!Schema::hasColumn('users', 'banned_by')) {
                // Relasi ke tabel admins (set null jika admin dihapus)
                $table->foreignId('banned_by')
                      ->nullable()
                      ->constrained('admins')
                      ->nullOnDelete()
                      ->after('ban_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // PENTING: Hapus Foreign Key dulu sebelum menghapus kolomnya
            // Array ['banned_by'] memberitahu Laravel untuk mencari constraint pada kolom tersebut
            $table->dropForeign(['banned_by']);
            
            // Setelah FK lepas, baru kolom bisa dihapus dengan aman
            $table->dropColumn(['is_banned', 'banned_at', 'ban_reason', 'banned_by']);
        });
    }
};