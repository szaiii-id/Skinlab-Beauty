<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMembership;
use App\Services\PointService;
use App\Services\FcmService; // Import FCM

class MembershipService
{
    protected $pointService;
    protected $fcmService;

    // Inject PointService
    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
        
        // Inisialisasi FcmService (bisa juga via dependency injection)
        $this->fcmService = new FcmService(); 
    }

    /**
     * Cek dan Update Level User berdasarkan total belanja
     * @param User $user
     * @param float $newTransactionAmount Nominal transaksi baru yang sudah lunas
     */
    public function checkAndUpgradeLevel(User $user, float $newTransactionAmount)
    {
        // 1. Ambil atau Buat Data Membership jika belum ada
        $membership = $user->membership ?: UserMembership::create(['user_id' => $user->id]);

        // 2. Tambahkan nominal transaksi baru ke total belanja seumur hidup
        $membership->increment('total_spend', $newTransactionAmount);
        
        // 3. Ambil data terbaru setelah update
        $currentSpend = $membership->refresh()->total_spend;
        $currentTier = $membership->tier;
        $newTier = $currentTier;
        $bonusPoints = 0;

        // --- ATURAN LEVEL ---
        // Silver: Belanja diatas 1.5 Juta
        if ($currentSpend >= 1500000 && $currentSpend < 5000000) {
            $newTier = 'Silver';
            $bonusPoints = 150; 
        } 
        // Gold: Belanja diatas 5 Juta
        elseif ($currentSpend >= 5000000) {
            $newTier = 'Gold';
            $bonusPoints = 750;
        }

        // --- PROSES UPGRADE ---
        // Definisikan hierarki level untuk membandingkan
        $levels = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3];
        
        // Cek jika level baru lebih tinggi dari level sekarang
        if (isset($levels[$newTier]) && isset($levels[$currentTier]) && $levels[$newTier] > $levels[$currentTier]) {
            
            // A. Update Database Membership
            $membership->update([
                'tier' => $newTier,
                'last_upgraded_at' => now()
            ]);

            // B. Beri Poin Bonus (Reward naik level)
            $this->pointService->addPoints(
                $user, 
                $bonusPoints, 
                'level_up_bonus', 
                "Selamat! Naik level ke $newTier"
            );

            // C. Kirim Notifikasi ke User via Firebase
            $this->fcmService->sendToUser(
                $user->id,
                "Level Up! Kamu sekarang {$newTier} Member 👑",
                "Selamat! Nikmati keuntungan eksklusif dan bonus poin sebagai member {$newTier}.",
                "/rewards" // Link ke halaman rewards
            );
        }
    }
}