<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\UserReward;
use App\Models\SkincareRoutine;
use App\Models\RoutineCompletion;
use App\Models\UserSkinProfile;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Ambil 5 transaksi terakhir
     */
    public function getRecentOrders(int $userId): Collection
    {
        return Order::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();
    }

    /**
     * Hitung Statistik User (Poin, Voucher, Skin Type, Routine)
     */
    public function getUserStats(User $user): array
    {
        // 1. Ambil Skin Profile Terakhir
        $skinProfile = UserSkinProfile::where('user_id', $user->id)->latest()->first();

        // 2. Hitung Voucher Aktif
        $voucherCount = UserReward::where('user_id', $user->id)
            ->where('is_used', false)
            ->where(function($q) {
                // Opsional: Cek expired jika ada kolom expires_at
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        // 3. Hitung Progress Routine (Logic Rumit dipindah kesini)
        $routineData = $this->calculateRoutineProgress($user->id);

        return [
            'points' => $user->current_points ?? 0,
            'voucher_count' => $voucherCount,
            'skin_type' => $skinProfile ? $skinProfile->skin_type : null,
            'routine_progress' => $routineData['percentage'],
            'routine_count' => $routineData['text_count']
        ];
    }

    /**
     * Helper Private untuk kalkulasi rutin
     */
    private function calculateRoutineProgress(int $userId): array
    {
        $totalRoutines = SkincareRoutine::where('user_id', $userId)->count();
        
        if ($totalRoutines === 0) {
            return ['percentage' => 0, 'text_count' => '0/0'];
        }

        // Ambil ID rutin milik user
        $routineIds = SkincareRoutine::where('user_id', $userId)->pluck('id');
        
        // Hitung berapa yang sudah dicentang HARI INI
        $completedToday = RoutineCompletion::whereIn('skincare_routine_id', $routineIds)
            ->where('year_month', now()->format('Y-m'))
            // Pastikan database support JSON query, jika tidak, logic ini harus diubah sedikit
            ->whereJsonContains('completed_days', (int) now()->day) 
            ->count();

        return [
            'percentage' => round(($completedToday / $totalRoutines) * 100),
            'text_count' => "{$completedToday}/{$totalRoutines}"
        ];
    }
}