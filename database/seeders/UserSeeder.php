<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        User::factory()->create([
            'name' => 'Admin Skin Lab',
            'email' => 'admin@skinlab.test',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        User::factory(5)->create([
            'role' => 'user',
            'password' => bcrypt('bahlil'),
        ]);
    }
}
