<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PointTransaction; // Import Model Transaksi Poin
use App\Services\PointService;   // Import Service Poin
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    protected $pointService;

    // Inject PointService agar bisa dipakai
    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    public function handle(Request $request)
    {
        // 1. Konfigurasi
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        try {
            $notif = new Notification();
        } catch (\Exception $e) {
            return response(['message' => 'Invalid notification'], 400);
        }

        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $orderId = $notif->order_id;
        $fraud = $notif->fraud_status;

        // 2. Cari Order (Eager load user untuk poin)
        $order = Order::with('user')->where('order_number', $orderId)->first();

        if (!$order) {
            return response(['message' => 'Order not found'], 404);
        }

        // 3. Update Status & Inject Poin
        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $order->update(['payment_status' => 'pending']);
                } else {
                    $order->update(['payment_status' => 'paid', 'order_status' => 'processing']);
                    // BERI POIN (Kartu Kredit Sukses)
                    $this->grantPoints($order);
                }
            }
        } else if ($transaction == 'settlement') {
            // BERI POIN (Transfer/Gopay Sukses)
            $order->update(['payment_status' => 'paid', 'order_status' => 'processing']);
            $this->grantPoints($order);
            
        } else if ($transaction == 'pending') {
            $order->update(['payment_status' => 'pending']);
        } else if ($transaction == 'deny') {
            $order->update(['payment_status' => 'failed', 'order_status' => 'cancelled']);
        } else if ($transaction == 'expire') {
            $order->update(['payment_status' => 'expired', 'order_status' => 'cancelled']);
        } else if ($transaction == 'cancel') {
            $order->update(['payment_status' => 'cancelled', 'order_status' => 'cancelled']);
        }

        return response(['message' => 'OK']);
    }

    /**
     * Logic Khusus Menghitung & Memberikan Poin
     */
    private function grantPoints(Order $order)
    {
        try {
            // A. Cek Duplikasi (PENTING!)
            // Jangan sampai midtrans kirim notif 2x, user dapat poin 2x.
            // Kita cek apakah sudah ada transaksi poin dengan deskripsi order ini.
            $exists = PointTransaction::where('user_id', $order->user_id)
                ->where('source_type', 'purchase')
                ->where('description', 'LIKE', "%{$order->order_number}%")
                ->exists();

            if ($exists) {
                return; // Stop jika sudah pernah dapat
            }

            // B. Hitung Poin (Rule: Rp 10.000 = 1 Poin)
            // Dihitung dari SUBTOTAL (Harga Barang), bukan Total (yang ada ongkir)
            // Gunakan floor (pembulatan ke bawah)
            $points = (int) floor($order->subtotal / 10000);

            // C. Masukkan ke Service jika poin > 0
            if ($points > 0) {
                $this->pointService->addPoints(
                    $order->user,
                    $points,
                    'purchase', // Source type
                    "Bonus Belanja Order #{$order->order_number}" // Description
                );
                
                Log::info("Points granted to User {$order->user_id}: {$points} pts for Order {$order->order_number}");
            }

        } catch (\Exception $e) {
            Log::error("Failed granting points for Order {$order->order_number}: " . $e->getMessage());
        }
    }
}