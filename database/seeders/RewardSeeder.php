<?php

namespace Database\Seeders;

use App\Models\Reward;
use App\Models\User;
use App\Models\UserReward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Katalog Hadiah Ada (Update atau Buat Baru)
        $r1 = Reward::updateOrCreate(
            ['name' => 'Diskon Rp 10.000'],
            [
                'description' => 'Potongan langsung.',
                'points_required' => 100, 
                'type' => 'discount_fixed',
                'value' => 10000,
                'min_spend' => 50000, // Syarat 50rb
                'stock' => 100,
            ]
        );

        // 2. BAGIKAN VOUCHER KE SEMUA USER (Looping)
        $users = User::all();

        foreach ($users as $user) {
            // Update Poin Modal
            $user->update(['current_points' => 5000]);

            // Cek apakah user ini sudah punya voucher sejenis (biar gak duplikat)
            $hasVoucher = UserReward::where('user_id', $user->id)
                ->where('reward_id', $r1->id)
                ->exists();

            if (!$hasVoucher) {
                UserReward::create([
                    'user_id' => $user->id,
                    'reward_id' => $r1->id,
                    'code' => 'GIFT-' . $user->id . '-' . strtoupper(uniqid()), // Kode unik per user
                    'is_used' => false,
                    'expires_at' => now()->addMonth(),
                ]);
            }
        }
    }
}


