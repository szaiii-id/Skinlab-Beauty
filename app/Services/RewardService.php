<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\UserReward;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class RewardService
{
    protected PointService $pointService;

    // Inject PointService untuk menangani pengurangan poin
    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    /**
     * Get active reward catalog
     */
    public function getCatalog(): Collection
    {
        return Reward::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('points_required', 'asc')
            ->get();
    }

    /**
     * Get user's active vouchers
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
     * Get formatted point history
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
                    'amount' => $t->amount, // e.g. +100 or -50
                    'description' => $t->description,
                    'date' => $t->created_at->format('d M Y'),
                    'is_positive' => $t->amount > 0
                ];
            })->toArray();
    }

    /**
     * Handle Reward Redemption Process
     */
    public function redeemReward(User $user, int $rewardId): void
    {
        DB::transaction(function () use ($user, $rewardId) {
            // 1. Lock Reward to prevent Race Condition on Stock
            $reward = Reward::where('id', $rewardId)->lockForUpdate()->first();

            if (!$reward) {
                throw new Exception("Reward not found.");
            }

            // 2. Validation
            if ($reward->stock <= 0) {
                throw new Exception("This reward is out of stock.");
            }

            if ($user->current_points < $reward->points_required) {
                throw new Exception("Insufficient points.");
            }

            // 3. Deduct Points via PointService
            $this->pointService->deductPoints(
                $user, 
                $reward->points_required, 
                'redemption', 
                "Redeemed: {$reward->name}"
            );

            // 4. Create User Voucher
            UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'code' => 'VCHR-' . strtoupper(uniqid()), // Generate unique code
                'is_used' => false,
                'expires_at' => now()->addDays(30) // Example: 30 days validity
            ]);

            // 5. Decrement Stock
            $reward->decrement('stock');
        });
    }
}