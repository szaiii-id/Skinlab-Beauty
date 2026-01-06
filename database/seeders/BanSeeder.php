<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use App\Models\BanRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class BanSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // 1. Ambil Super Admin (sebagai Reviewer/Penyetuju)
        $superAdmin = Admin::where('role', Admin::ROLE_SUPER_ADMIN)->first();

        // 2. Ambil Admin Staff (sebagai Requester/Peminta)
        // Sesuai model Anda: Marketing atau Warehouse
        $staffAdmins = Admin::whereIn('role', [Admin::ROLE_MARKETING, Admin::ROLE_WAREHOUSE])->get();

        if (!$superAdmin || $staffAdmins->isEmpty()) {
            $this->command->error("❌ Data Admin tidak lengkap. Pastikan ada Super Admin dan (Marketing/Warehouse) di tabel admins.");
            return;
        }

        $this->command->info("🚀 Memulai BanSeeder dengan Role Valid (Marketing/Warehouse)...");

        // 3. Ambil User untuk di-ban (Random 50 orang)
        $users = User::where('is_banned', false)->inRandomOrder()->limit(50)->get();
        $banRequestsBuffer = [];

        foreach ($users as $user) {
            $scenario = rand(1, 100);
            $createdAt = Carbon::now()->subDays(rand(1, 180));

            // Tentukan Alasan
            $reason = $faker->randomElement([
                BanRequest::REASON_RETURN_ABUSE,
                BanRequest::REASON_FRAUD,
                BanRequest::REASON_TOXIC_BEHAVIOR,
                BanRequest::REASON_PAYMENT_ISSUE
            ]);

            // LOGIC REALISTIS: Siapa yang request?
            // Jika masalah Retur Barang -> Warehouse yang request
            // Jika masalah Fraud/Toxic -> Marketing yang request
            if ($reason === BanRequest::REASON_RETURN_ABUSE) {
                $requester = $staffAdmins->where('role', Admin::ROLE_WAREHOUSE)->first();
            } else {
                $requester = $staffAdmins->where('role', Admin::ROLE_MARKETING)->first();
            }

            // Fallback: Jika admin spesifik tidak ketemu, pakai staff acak yg ada
            if (!$requester) {
                $requester = $staffAdmins->random();
            }

            // --- SKENARIO 1: PENDING REQUEST (20%) ---
            if ($scenario <= 20) {
                $banRequestsBuffer[] = [
                    'user_id' => $user->id,
                    'requested_by' => $requester->id, // ID Marketing/Warehouse
                    'reason' => $reason,
                    'description' => "Request ban manual oleh staff {$requester->role}.",
                    'evidence' => json_encode(['notes' => 'Suspicious activity detected']),
                    'status' => BanRequest::STATUS_PENDING,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_notes' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            } 
            
            // --- SKENARIO 2: REJECTED REQUEST (20%) ---
            elseif ($scenario <= 40) {
                $banRequestsBuffer[] = [
                    'user_id' => $user->id,
                    'requested_by' => $requester->id,
                    'reason' => $reason,
                    'description' => "User sering membatalkan pesanan.",
                    'evidence' => json_encode(['notes' => 'Chat log attached']),
                    'status' => BanRequest::STATUS_REJECTED,
                    'reviewed_by' => $superAdmin->id, // Direview Super Admin
                    'reviewed_at' => $createdAt->copy()->addHours(2),
                    'review_notes' => 'Bukti kurang kuat, pantau dulu.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addHours(2),
                ];
            }

            // --- SKENARIO 3: APPROVED BAN (60% - User BANNED) ---
            else {
                $reviewedAt = $createdAt->copy()->addHours(rand(1, 24));
                
                // Masukkan ke tabel Request
                $banRequestsBuffer[] = [
                    'user_id' => $user->id,
                    'requested_by' => $requester->id,
                    'reason' => $reason,
                    'description' => "Pelanggaran berat (TOS Violation).",
                    'evidence' => json_encode(['notes' => 'Bukti valid']),
                    'status' => BanRequest::STATUS_APPROVED,
                    'reviewed_by' => $superAdmin->id,
                    'reviewed_at' => $reviewedAt,
                    'review_notes' => 'Approved. Banned permanently.',
                    'created_at' => $createdAt,
                    'updated_at' => $reviewedAt,
                ];

                // Update User Table (PENTING)
                DB::table('users')->where('id', $user->id)->update([
                    'is_banned' => true,
                    'banned_at' => $reviewedAt,
                    'ban_reason' => $reason,
                    'banned_by' => $superAdmin->id
                ]);
            }
        }

        // Bulk Insert
        if (!empty($banRequestsBuffer)) {
            DB::table('ban_requests')->insert($banRequestsBuffer);
        }

        $this->command->info("✅ BanSeeder Selesai. Data request dibuat oleh Marketing & Warehouse.");
    }
}