<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\SkincareRoutine;
use App\Services\FcmService; // Import Service FCM
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduler untuk Notifikasi Skincare Routine
 * Berjalan setiap menit untuk mengecek jadwal user.
 */
Schedule::call(function () {
    // 1. Cek jam server sekarang (UTC)
    // Pastikan format H:i:00 agar cocok dengan database
    $nowUTC = now('UTC')->format('H:i:00'); 

    // 2. Cari semua jadwal yang waktunya SAMA dengan jam UTC sekarang
    $routines = SkincareRoutine::where('reminder_time', $nowUTC)
        ->where('is_reminder_active', true)
        ->with('product') // Eager load produk biar query ringan
        ->get();

    if ($routines->isEmpty()) {
        return;
    }

    // 3. Inisialisasi Service FCM
    $fcmService = new FcmService();

    foreach ($routines as $routine) {
        // 4. Cek Anti-Spam: Apakah hari ini sudah dicentang?
        // Kita cek relasi 'currentMonthCompletion' dan array 'completed_days'
        $isDone = $routine->currentMonthCompletion()
            ->whereJsonContains('completed_days', now()->day)
            ->exists();

        if (!$isDone) {
            // Tentukan nama produk (dari katalog atau manual)
            $productName = $routine->product ? $routine->product->name : $routine->custom_product_name;
            
            // 5. Kirim Notifikasi via Service
            // Ini akan mengirim ke SEMUA device milik user tersebut (HP & Laptop)
            $fcmService->sendToUser(
                $routine->user_id,
                "It`s time for your Skincare Routine ✨",
                "Don`t forget to use {$productName} now!",
                "/my-routine" // Link saat notifikasi diklik
            );
        }
    }
})->everyMinute();

Schedule::call(function () {
    DB::table('notifications')
        ->whereNotNull('read_at') // Hanya yang sudah dibaca
        ->where('created_at', '<', now()->subDays(30)) // Lebih dari 30 hari
        ->delete();
        
    // Opsi Ekstrem: Hapus yang BELUM dibaca pun kalau sudah 60 hari (Biar database bersih)
    DB::table('notifications')
        ->where('created_at', '<', now()->subDays(60))
        ->delete();
        
})->daily();