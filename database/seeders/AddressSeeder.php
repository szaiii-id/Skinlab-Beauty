<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();
        $faker = Faker::create('id_ID');

        // Ambil sampel wilayah untuk di-random (agar tidak query berat)
        $validLocations = DB::table('indonesia_districts')
            ->join('indonesia_cities', 'indonesia_districts.city_code', '=', 'indonesia_cities.code')
            ->join('indonesia_provinces', 'indonesia_cities.province_code', '=', 'indonesia_provinces.code')
            ->select(
                'indonesia_districts.code as district_code',
                'indonesia_cities.code as city_code',
                'indonesia_provinces.code as province_code',
                'indonesia_cities.name as city_name'
            )
            ->inRandomOrder()
            ->limit(500) // Cache 500 lokasi di memori
            ->get();

        if ($validLocations->isEmpty()) {
            $this->command->error("❌ Data Wilayah Kosong! Seed Laravolt dulu.");
            return;
        }

        $this->command->info("🚀 Seeding Addresses (100% User coverage)...");

        // Ambil semua user, chunk per 1000
        DB::table('users')->orderBy('id')->chunk(1000, function ($users) use ($faker, $validLocations) {
            
            $buffer = [];
            $now = now();

            foreach ($users as $user) {
                $loc = $validLocations->random();

                $buffer[] = [
                    'user_id'       => $user->id,
                    'receiver_name' => $user->name,
                    'phone_number'  => '08' . $faker->numerify('##########'),
                    'province_code' => $loc->province_code,
                    'city_code'     => $loc->city_code,
                    'district_code' => $loc->district_code,
                    'full_address'  => $faker->streetAddress . ", " . $loc->city_name,
                    'postal_code'   => $faker->postcode,
                    'latitude'      => $faker->latitude(-10, 5),
                    'longitude'     => $faker->longitude(95, 140),
                    'komerce_destination_id' => rand(1000, 20000),
                    'type'          => 'home',
                    'is_default'    => true, // Wajib true
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            DB::table('user_addresses')->insert($buffer);
            $this->command->getOutput()->write('.');
        });

        $this->command->info("\n✅ Selesai AddressSeeder.");
    }
}