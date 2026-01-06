<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSkinProfile; // Pastikan model ini ada
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class SkinAnalysisSeeder extends Seeder
{
    // Konstan sesuai Service Anda
    const SKIN_TYPES = [
        'Dry Skin',
        'Normal Skin',
        'Oily Skin',
        'Combination Skin'
    ];

    const CONCERNS_LIST = [
        'Texture',
        'Dark Circles',
        'Dark Spots',
        'Acne Scars',
        'Redness',
        'Sensitive',
        'Anti Aging',
        'Pores',
        'Blackheads'
    ];

    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $this->command->info("🚀 Memulai seeding User Skin Profile...");
        
        // 1. Ambil User (5000 sampel cukup)
        $users = User::select('id')->inRandomOrder()->limit(5000)->get();

        if ($users->isEmpty()) {
            $this->command->error("❌ User kosong. Jalankan UserSeeder dulu.");
            return;
        }

        $profilesBuffer = [];
        $batchSize = 500;
        $now = now();

        foreach ($users as $user) {
            // Skenario: Hanya 60% user yang mengisi Skin Analysis
            if (rand(1, 100) > 60) continue;

            // 1. Pilih Tipe Kulit Random
            $skinType = $faker->randomElement(self::SKIN_TYPES);

            // 2. Pilih Masalah Kulit (1 s/d 4 masalah)
            // Gunakan array_rand atau randomElements dari faker
            $concernsCount = rand(1, 4);
            $selectedConcerns = $faker->randomElements(self::CONCERNS_LIST, $concernsCount);

            // 3. Buat Data Answers Dummy (Opsional, tapi bagus untuk kelengkapan)
            // Format dummy JSON saja, karena Service Anda menyimpannya
            $answersDummy = json_encode([
                'q1' => $faker->randomElement(['A', 'B', 'C', 'D']),
                'q2' => $faker->randomElement(['Yes', 'No']),
                'custom_note' => $faker->sentence(),
            ]);

            $profilesBuffer[] = [
                'user_id' => $user->id,
                'skin_type' => $skinType,
                'skin_concerns' => json_encode($selectedConcerns), // Simpan sebagai JSON
                'answers_data' => $answersDummy,
                'created_at' => $now->copy()->subDays(rand(1, 300)), // Profile dibuat acak setahun terakhir
                'updated_at' => $now,
            ];

            // Bulk Insert
            if (count($profilesBuffer) >= $batchSize) {
                DB::table('user_skin_profiles')->insertOrIgnore($profilesBuffer);
                $profilesBuffer = [];
            }
        }

        // Insert Sisa
        if (!empty($profilesBuffer)) {
            DB::table('user_skin_profiles')->insertOrIgnore($profilesBuffer);
        }

        $this->command->info("✅ Sukses! Data Skin Profile dummy berhasil dibuat.");
    }
}