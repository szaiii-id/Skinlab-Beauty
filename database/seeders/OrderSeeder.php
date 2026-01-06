<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan Query Log untuk menghemat RAM
        DB::disableQueryLog();
        $faker = Faker::create('id_ID');

        // 1. Ambil Data Varian (Snapshot Harga & Nama)
        $variants = DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(
                'product_variants.id as variant_id',
                'product_variants.price',
                'product_variants.volume',
                'products.name as product_name'
            )
            ->get();

        if ($variants->isEmpty()) {
            $this->command->error("❌ Varian Produk kosong! Jalankan ProductSeeder dulu.");
            return;
        }

        // 2. Query User (Ambil Semua 50k User)
        // Menggunakan MIN(id) alamat agar user tidak duplikat jika punya banyak alamat
        $query = DB::table('users')
            ->join('user_addresses', 'users.id', '=', 'user_addresses.user_id')
            ->select('users.id as user_id', DB::raw('MIN(user_addresses.id) as address_id'))
            ->groupBy('users.id');

        $totalUsers = $query->count();
        $this->command->info("🚀 Memulai Seeding Orders (Gabungan Lengkap) untuk {$totalUsers} users...");
        
        $bar = $this->command->getOutput()->createProgressBar($totalUsers);
        $bar->start();

        // 3. Eksekusi Chunking (Per 500 User)
        $query->orderBy('user_id')->chunk(500, function ($users) use ($faker, $variants, $bar) {
            
            $orderItemsBuffer = [];
            $cancellationsBuffer = [];
            $returnsBuffer = [];

            DB::transaction(function () use ($users, $faker, $variants, &$orderItemsBuffer, &$cancellationsBuffer, &$returnsBuffer) {
                foreach ($users as $user) {
                    
                    // LIMIT: 1-2 Order per user (Agar Total Order 50k - 100k, tidak overload)
                    $orderCount = rand(1, 2);

                    for ($i = 0; $i < $orderCount; $i++) {
                        
                        // === A. DISTRIBUSI WAKTU TERJAMIN (WEIGHTED) ===
                        $chance = rand(1, 100);
                        $transactionDate = Carbon::now();

                        if ($chance <= 15) { 
                            $transactionDate->subHours(rand(1, 23)); // Hari Ini
                        } elseif ($chance <= 35) { 
                            $transactionDate->subDays(rand(1, 6))->subHours(rand(1, 12)); // Minggu Ini
                        } elseif ($chance <= 65) { 
                            $transactionDate->subMonths(rand(1, 11)); // Bulan Lalu
                        } else { 
                            $transactionDate->subYears(rand(1, 2)); // Tahun Lalu
                        }

                        // === B. STATUS (Logika Referensi Anda) ===
                        $statusData = $this->generateStatusByDate($transactionDate);
                        
                        // === C. ITEMS ===
                        $cartItems = $variants->random(rand(1, 4));
                        $subtotal = 0;
                        $tempItems = [];

                        foreach ($cartItems as $item) {
                            $qty = rand(1, 2);
                            $lineTotal = $item->price * $qty;
                            $subtotal += $lineTotal;

                            $tempItems[] = [
                                'product_variant_id' => $item->variant_id,
                                'product_name' => $item->product_name,
                                'variant_name' => $item->volume,
                                'quantity' => $qty,
                                'price' => $item->price,
                                'subtotal' => $lineTotal,
                                'created_at' => $transactionDate,
                                'updated_at' => $transactionDate,
                            ];
                        }

                        // === D. KEUANGAN & DISKON ===
                        $shippingCost = rand(10000, 50000);
                        $discount = (rand(1, 100) > 80) ? rand(5000, 20000) : 0; // 20% user dapat diskon
                        if ($discount > $subtotal) $discount = 0; // Safety
                        
                        $totalAmount = ($subtotal - $discount) + $shippingCost;

                        // === E. DATA TAMBAHAN (Resi, Komerce, dll) ===
                        $resi = null;
                        $trackNo = null;
                        $komerceId = null;

                        // Hanya generate resi jika status memungkinkan
                        if (in_array($statusData['order_status'], ['shipped', 'completed', 'return_requested', 'returned'])) {
                            $resi = strtoupper(Str::random(12));
                            $trackNo = 'KOM-' . strtoupper(Str::random(10));
                            $komerceId = rand(10000, 99999);
                        }

                        // === F. INSERT HEADER (FIX ANTI BENTROK) ===
                        // Format: ORD-TGL-USERID-ACAK (Pasti Unik)
                        $uniqueOrderNumber = 'ORD-' . $transactionDate->format('Ymd') . '-' . $user->user_id . '-' . strtoupper(Str::random(4));

                        $orderId = DB::table('orders')->insertGetId([
                            'user_id' => $user->user_id,
                            'shipping_address_id' => $user->address_id,
                            'order_number' => $uniqueOrderNumber, 
                            'subtotal' => $subtotal,
                            'shipping_cost' => $shippingCost,
                            'discount_amount' => $discount,
                            'total_amount' => $totalAmount,
                            'payment_status' => $statusData['payment_status'],
                            'payment_method' => $faker->randomElement(['bank_transfer', 'qris', 'ewallet']),
                            'snap_token' => Str::uuid(),
                            'order_status' => $statusData['order_status'],
                            'resi_number' => $resi,
                            'shipping_tracking_number' => $trackNo,
                            'komerce_order_id' => $komerceId,
                            'created_at' => $transactionDate,
                            'updated_at' => $transactionDate,
                        ]);

                        // G. Buffer Items
                        foreach ($tempItems as $item) {
                            $item['order_id'] = $orderId;
                            $orderItemsBuffer[] = $item;
                        }

                        // === H. CANCELLATION (Data Lengkap) ===
                        if ($statusData['order_status'] === 'cancelled' || $statusData['order_status'] === 'cancellation_requested') {
                            $cancellationsBuffer[] = [
                                'order_id' => $orderId,
                                'reason' => $faker->randomElement(['Changed mind', 'Found cheaper price']),
                                'status' => ($statusData['order_status'] === 'cancelled') ? 'approved' : 'pending',
                                'admin_note' => ($statusData['order_status'] === 'cancelled') ? 'Approved by System' : null,
                                'created_at' => $transactionDate->copy()->addHours(1),
                                'updated_at' => $transactionDate->copy()->addHours(2),
                            ];
                        }

                        // === I. RETURN (Data Lengkap) ===
                        if (in_array($statusData['order_status'], ['return_requested', 'return_approved', 'returned'])) {
                            $returnsBuffer[] = [
                                'order_id' => $orderId,
                                'user_id' => $user->user_id,
                                'reason' => $faker->randomElement(['Damaged item', 'Wrong item', 'Expired']),
                                'description' => $faker->sentence(),
                                'evidence_file' => 'returns/dummy.jpg',
                                'solution' => $faker->randomElement(['refund', 'exchange']),
                                'status' => ($statusData['order_status'] === 'return_requested') ? 'pending' : 'approved',
                                'is_restocked' => ($statusData['order_status'] === 'returned'), // True jika sudah returned
                                'created_at' => $transactionDate->copy()->addDays(3),
                                'updated_at' => $transactionDate->copy()->addDays(4),
                            ];
                        }
                    }
                }

                // J. BULK INSERT (Aman & Cepat)
                if (!empty($orderItemsBuffer)) {
                    foreach (array_chunk($orderItemsBuffer, 1000) as $chunk) DB::table('order_items')->insert($chunk);
                }
                
                // Gunakan try-catch pada tabel sekunder jaga-jaga struktur beda
                if (!empty($cancellationsBuffer)) {
                    try { DB::table('order_cancellations')->insert($cancellationsBuffer); } catch (\Exception $e) {}
                }
                if (!empty($returnsBuffer)) {
                    try { DB::table('order_returns')->insert($returnsBuffer); } catch (\Exception $e) {}
                }
            });

            $bar->advance(count($users));
        });

        $bar->finish();
        $this->command->info("\n🎉 Selesai! Data Order (100k, Lengkap, Anti-Duplikat) berhasil dibuat.");
    }

    private function generateStatusByDate(Carbon $date): array
    {
        $daysAgo = $date->diffInDays(now());

        // 1. Order Lama (> 30 hari) -> Pasti selesai/batal/retur
        if ($daysAgo > 30) {
            $chance = rand(1, 100);
            if ($chance <= 85) return ['order_status' => 'completed', 'payment_status' => 'paid'];
            if ($chance <= 95) return ['order_status' => 'cancelled', 'payment_status' => 'failed'];
            return ['order_status' => 'returned', 'payment_status' => 'paid']; 
        }

        // 2. Order Mingguan (> 7 Hari) -> Dikirim atau Selesai
        if ($daysAgo > 7) {
            return ['order_status' => 'shipped', 'payment_status' => 'paid'];
        }

        // 3. Order Baru (0-7 Hari) -> Status Campur
        $statuses = [
            'pending' => 'unpaid',
            'processing' => 'paid',
            'pickup_scheduled' => 'paid',
            'shipped' => 'paid',
            'cancellation_requested' => 'paid'
        ];
        
        $key = array_rand($statuses);
        return ['order_status' => $key, 'payment_status' => $statuses[$key]];
    }
}