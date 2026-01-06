<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ProductVariant;
use App\Models\User;
use Faker\Factory as Faker;

class StockHistorySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');
        
        // Ambil Data Varian & User
        $variants = ProductVariant::all();
        $adminId = User::first()->id ?? 1;

        if ($variants->isEmpty()) {
            $this->command->error("❌ Varian kosong. Jalankan ProductSeeder dulu.");
            return;
        }

        $historyBuffer = [];

        foreach ($variants as $variant) {
            // Simulasi 5-10 transaksi per produk
            $transactions = rand(5, 10);
            $currentStockTracker = $variant->stock; // Start calculation from current

            for ($i = 0; $i < $transactions; $i++) {
                
                // 80% Penjualan (Sale), 20% Restock
                $isSale = rand(1, 100) <= 80; 
                $qty = rand(1, 5);

                if ($isSale) {
                    $type = 'sale';
                    $change = -$qty;
                    $note = "Order #" . rand(1000, 9999);
                } else {
                    $type = 'restock';
                    $change = $qty * 10; // Restock biasanya banyak
                    $note = "Supply dari Vendor";
                }

                // Hitung mundur stok (dummy logic)
                $currentStockTracker += $change; 

                $historyBuffer[] = [
                    'product_variant_id' => $variant->id,
                    'type'               => $type,
                    'qty_change'         => $change,
                    'current_stock'      => max(0, $currentStockTracker), // Stok saat kejadian
                    'note'               => $note,
                    'reference_id'       => rand(100, 999), // Dummy Order ID
                    'user_id'            => $adminId,
                    'created_at'         => $faker->dateTimeBetween('-3 months', 'now'),
                    'updated_at'         => now(),
                ];
            }
        }

        // Insert Batch
        foreach (array_chunk($historyBuffer, 500) as $chunk) {
            DB::table('stock_histories')->insert($chunk);
        }

        $this->command->info("✅ Stock History Dummy berhasil dibuat.");
    }
}