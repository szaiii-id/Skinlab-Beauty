<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\UserReward;
use App\Models\User;
use App\Models\PointTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class RewardService
{
    // Key Cache untuk Katalog User
    public const CACHE_KEY_CATALOG = 'rewards:catalog';

    /**
     * Helper: Hapus Cache Katalog (Dipanggil Admin saat Create/Update/Delete)
     */
    public function clearCatalogCache(): void
    {
        Cache::forget(self::CACHE_KEY_CATALOG);
    }

    /**
     * User: Ambil Katalog (Cached)
     */
    public function getCatalog(): Collection
    {
        return Cache::remember(self::CACHE_KEY_CATALOG, 60 * 60 * 6, function () {
            return Reward::where('is_active', true)
                ->where('stock', '>', 0)
                ->where('is_claim_only', false)
                ->orderBy('points_required', 'asc')
                ->get();
        });
    }

    /**
     * User: Ambil Voucher Milik User
     */
    public function getUserVouchers(int $userId): Collection
    {
        return UserReward::with('reward')
            ->where('user_id', $userId)
            ->where('is_used', false)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();
    }

    /**
     * User: Riwayat Poin
     */
    public function getPointHistory(int $userId, int $limit = 10): array
    {
        return PointTransaction::where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'amount' => $t->amount,
                    'description' => $t->description,
                    'date' => $t->created_at->format('d M Y'),
                    'is_positive' => $t->amount > 0
                ];
            })->toArray();
    }

    /**
     * CORE LOGIC 1: Tukar Poin (User Action)
     */
    public function redeemReward(User $user, int $rewardId): UserReward
    {
        return DB::transaction(function () use ($user, $rewardId) {
            
            $reward = Reward::where('id', $rewardId)->lockForUpdate()->first();

            // Validasi
            if (!$reward) throw new Exception("Reward not found.");
            if (!$reward->is_active) throw new Exception("Reward is currently inactive.");
            if ($reward->is_claim_only) throw new Exception("This reward cannot be redeemed with points.");
            
            if ($reward->stock <= 0) throw new Exception("Out of stock!");

            if ($user->current_points < $reward->points_required) {
                throw new Exception("Insufficient points.");
            }

            if ($reward->max_per_user > 0) {
                $count = UserReward::where('user_id', $user->id)->where('reward_id', $rewardId)->count();
                if ($count >= $reward->max_per_user) {
                    throw new Exception("Redemption limit reached for this reward.");
                }
            }

            // 1. Potong Poin
            PointTransaction::create([
                'user_id' => $user->id,
                'amount' => -($reward->points_required),
                'source_type' => 'redeem',
                'description' => "Redeem: {$reward->name}"
            ]);
            $user->decrement('current_points', $reward->points_required);

            // 2. Buat Voucher (Snapshot Data)
            $userReward = UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'code' => 'RWD-' . strtoupper(Str::random(8)),
                'source' => 'redeem',
                'type' => $reward->type,
                'value' => $reward->value,
                'min_spend' => $reward->min_spend,
                'expires_at' => now()->addDays($reward->validity_days),
                'is_used' => false
            ]);

            // 3. Kurangi Stok
            $reward->decrement('stock');

            $this->clearCatalogCache();

            return $userReward;
        });
    }

    /**
     * CORE LOGIC 2: Gift / Claim Gratis (Admin Action)
     * Digunakan oleh fitur "Send Gift" di Admin Panel
     */
    public function claimReward(User $user, int $rewardId): UserReward
    {
        return DB::transaction(function () use ($user, $rewardId) {
            
            $reward = Reward::where('id', $rewardId)->lockForUpdate()->first();

            // Validasi (Lebih longgar, tanpa cek poin & is_claim_only)
            if (!$reward) throw new Exception("Reward not found.");
            if (!$reward->is_active) throw new Exception("Reward is inactive.");
            if ($reward->stock <= 0) throw new Exception("Out of stock!");

            // Tetap cek limit per user agar tidak double send (Opsional, bisa dihapus jika Admin boleh kirim berkali-kali)
            if ($reward->max_per_user > 0) {
                $count = UserReward::where('user_id', $user->id)->where('reward_id', $rewardId)->count();
                if ($count >= $reward->max_per_user) {
                    throw new Exception("User already reached the limit for this reward.");
                }
            }

            // 1. Buat Voucher Langsung (Tanpa Potong Poin)
            $userReward = UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'code' => 'GIFT-' . strtoupper(Str::random(8)), // Prefix GIFT biar spesial
                'source' => 'gift',
                'type' => $reward->type,
                'value' => $reward->value,
                'min_spend' => $reward->min_spend,
                'expires_at' => now()->addDays($reward->validity_days),
                'is_used' => false
            ]);

            // 2. Kurangi Stok
            $reward->decrement('stock');

            $this->clearCatalogCache();

            return $userReward;
        });
    }
}