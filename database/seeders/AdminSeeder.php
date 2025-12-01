<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@skinlab.com', // Email untuk Anda login
            'password' => Hash::make('password123'), // Password sementara
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        
        // Opsional: Buat contoh akun gudang sekalian
        Admin::create([
            'name' => 'Staf Gudang',
            'email' => 'warehouse@skinlab.com',
            'password' => Hash::make('password123'),
            'role' => 'warehouse',
            'is_active' => true,
        ]);

        Admin::create([
            'name' => 'Marketing',
            'email' => 'marketing@skinlab.com',
            'password' => Hash::make('password123'),
            'role' => 'marketing',
            'is_active' => true,
        ]);
    }
}