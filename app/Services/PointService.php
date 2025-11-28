<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\UserReward;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PointService
{
    // Tambah Poin (Earning)
    public function addPoints(User $user, int $amount, string $source, string $desc)
    {
        DB::transaction(function () use ($user, $amount, $source, $desc) {
            PointTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'source_type' => $source,
                'description' => $desc
            ]);
            $user->increment('current_points', $amount);
        });
    }

    // Tukar Poin (Redeeming)
    public function redeemReward(User $user, Reward $reward)
    {
        if ($user->current_points < $reward->points_required) {
            throw new \Exception("Poin tidak cukup.");
        }

        if ($reward->stock <= 0) {
            throw new \Exception("Stok hadiah habis.");
        }

        return DB::transaction(function () use ($user, $reward) {
            // 1. Kurangi Poin
            PointTransaction::create([
                'user_id' => $user->id,
                'amount' => -($reward->points_required),
                'source_type' => 'redeem',
                'description' => "Tukar: " . $reward->name
            ]);
            $user->decrement('current_points', $reward->points_required);

            // 2. Buat Voucher User
            $code = 'RWD-' . strtoupper(Str::random(6));
            
            $userReward = UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'code' => $code,
                'expires_at' => now()->addYear(), // Berlaku 1 tahun
            ]);

            // 3. Kurangi Stok
            $reward->decrement('stock');

            return $userReward;
        });
    }
}