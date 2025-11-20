<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        // Konfigurasi dipanggil otomatis saat Class ini dibuat
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function getSnapToken($order, $user)
    {
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) ceil($order->total_amount), // Pastikan Integer & Bulat
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ];

        // HAPUS TRY-CATCH AGAR ERROR MUNCUL
        // try {
            return Snap::getSnapToken($params);
        // } catch (\Exception $e) {
        //    return null;
        // }
    }
}