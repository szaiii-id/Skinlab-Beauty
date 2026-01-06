<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::unsetEventDispatcher();

        $password = Hash::make('password123'); 
        
        $totalUsers = 50000;
        $chunkSize = 1000; 

        $this->command->info("🚀 Memulai seeding {$totalUsers} users (Safe Mode)...");
        $startTime = microtime(true);

        for ($i = 0; $i < ($totalUsers / $chunkSize); $i++) {
            
            $users = User::factory()->count($chunkSize)->make([
                'password' => $password,
                // Kita tidak set email di sini, biarkan factory yang buat
            ]);

            $users->makeVisible(['password', 'remember_token']); 
            
            $usersArray = $users->toArray();

            foreach ($usersArray as &$user) {
                // Manipulasi Tanggal
                $randomDate = Carbon::now()
                                ->subDays(rand(0, 730)) 
                                ->subMinutes(rand(0, 1440))
                                ->format('Y-m-d H:i:s');

                $user['created_at'] = $randomDate;
                $user['updated_at'] = $randomDate;
                $user['email_verified_at'] = $randomDate;
                $user['two_factor_confirmed_at'] = null;
            }

            // --- PERBAIKAN DI SINI ---
            // Gunakan insertOrIgnore agar jika ada email kembar, tidak error/crash
            User::insertOrIgnore($usersArray);

            $this->command->info("✅ Chunk " . ($i + 1) . " selesai.");
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->command->info("🎉 Selesai! {$totalUsers} users diproses dalam {$duration} detik.");
    }
}