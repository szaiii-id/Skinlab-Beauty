<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        
        $this->command->info("🚀 Memulai seeding Reviews...");
        $startTime = microtime(true);

        // 1. Ambil Order yang SUDAH SELESAI (Completed)
        // Kita limit 2000 order saja agar proses tidak terlalu lama
        $completedOrders = Order::where('order_status', 'completed')
            ->select('id', 'user_id', 'updated_at') // updated_at dianggap tgl selesai order
            ->inRandomOrder()
            ->limit(2000)
            ->with(['items.productVariant']) // Load items untuk tahu produk apa yg dibeli
            ->get();

        if ($completedOrders->isEmpty()) {
            $this->command->error("❌ Tidak ada order 'completed'. Jalankan OrderSeeder dulu!");
            return;
        }

        $reviewsBuffer = [];
        $batchSize = 500;

        foreach ($completedOrders as $order) {
            
            // Random: Tidak semua user rajin kasih review (hanya 70%)
            if (rand(1, 100) > 70) continue;

            // Loop setiap item dalam order tersebut
            foreach ($order->items as $item) {
                // Pastikan produknya masih ada (karena relasi bisa nullOnDelete)
                if (!$item->productVariant || !$item->productVariant->product_id) continue;

                $productId = $item->productVariant->product_id;

                // Tentukan Rating (Biasanya user yg review kasih bintang bagus)
                // Distribusi: 5 (50%), 4 (30%), 3 (10%), 2 (5%), 1 (5%)
                $rating = $faker->randomElement([5, 5, 5, 5, 5, 4, 4, 4, 3, 2, 1]);

                // Komentar Dummy sesuai rating
                $comment = $this->generateComment($rating, $faker);

                // Admin Reply Logic (Random 30% direply admin)
                $adminReply = null;
                $replyAt = null;
                if (rand(1, 100) <= 30) {
                    $adminReply = ($rating >= 4) 
                        ? "Terima kasih Kak {$faker->firstName}, semoga cocok ya! Ditunggu order selanjutnya 💖"
                        : "Mohon maaf atas ketidaknyamanannya Kak. Silakan hubungi CS kami untuk solusi lebih lanjut ya 🙏";
                    $replyAt = Carbon::parse($order->updated_at)->addDays(rand(1, 7));
                }

                $reviewsBuffer[] = [
                    'user_id' => $order->user_id,
                    'product_id' => $productId,
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'is_hidden' => false, // Default tampil
                    'image' => (rand(1, 100) <= 20) ? 'reviews/dummy.jpg' : null, // 20% user upload foto
                    'admin_reply' => $adminReply,
                    'reply_at' => $replyAt,
                    'created_at' => Carbon::parse($order->updated_at)->addDays(rand(0, 3)), // Review H+0 s/d H+3 setelah barang sampai
                    'updated_at' => Carbon::parse($order->updated_at)->addDays(rand(0, 3)),
                ];
            }

            // Bulk Insert jika buffer penuh
            if (count($reviewsBuffer) >= $batchSize) {
                DB::table('reviews')->insertOrIgnore($reviewsBuffer); // Pakai insertOrIgnore biar kalau ada duplikat diskip
                $reviewsBuffer = [];
            }
        }

        // Insert sisa data terakhir
        if (!empty($reviewsBuffer)) {
            DB::table('reviews')->insertOrIgnore($reviewsBuffer);
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->command->info("🎉 Review Seeder Selesai dalam {$duration} detik.");
    }

    /**
     * Generate komentar realistis berdasarkan bintang
     */
    private function generateComment(int $rating, $faker): string
    {
        if ($rating == 5) {
            return $faker->randomElement([
                'Bagus banget, cocok di kulit aku!', 
                'Pengiriman cepat, packing aman bubble wrap tebal.', 
                'Suka banget sama teksturnya, bakal langganan.',
                'Terbaik! Harga murah tapi kualitas oke.'
            ]);
        } elseif ($rating == 4) {
            return $faker->randomElement([
                'Produk bagus, cuma pengiriman agak lama.', 
                'Lumayan cocok, baru coba seminggu.', 
                'Barang sesuai pesanan, thanks seller.',
                'Oke sih, tapi wanginya agak menyengat dikit.'
            ]);
        } elseif ($rating == 3) {
            return $faker->randomElement([
                'Biasa aja sih efeknya di aku.', 
                'Pengiriman lama banget kurirnya nyasar.', 
                'Isinya dikit ternyata, kirain botol gede.'
            ]);
        } else {
            return $faker->randomElement([
                'Gak cocok, malah bikin breakout parah.', 
                'Kecewa, barangnya tumpah pas nyampe.', 
                'Respon penjual lambat banget.',
                'Jangan beli disini, pengiriman lama.'
            ]);
        }
    }
}