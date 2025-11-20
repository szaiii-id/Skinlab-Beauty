<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Konfigurasi (Harus sama dengan Checkout)
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        try {
            // 2. Terima Notifikasi
            $notif = new Notification();
        } catch (\Exception $e) {
            return response(['message' => 'Invalid notification'], 400);
        }

        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $orderId = $notif->order_id;
        $fraud = $notif->fraud_status;

        // 3. Cari Order
        $order = Order::where('order_number', $orderId)->first();

        if (!$order) {
            return response(['message' => 'Order not found'], 404);
        }

        // 4. Update Status Database
        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $order->update(['payment_status' => 'pending']);
                } else {
                    $order->update(['payment_status' => 'paid']);
                }
            }
        } else if ($transaction == 'settlement') {
            // INI YANG PALING PENTING (Lunas)
            $order->update(['payment_status' => 'paid']);
        } else if ($transaction == 'pending') {
            $order->update(['payment_status' => 'pending']);
        } else if ($transaction == 'deny') {
            $order->update(['payment_status' => 'failed']);
        } else if ($transaction == 'expire') {
            $order->update(['payment_status' => 'expired']);
        } else if ($transaction == 'cancel') {
            $order->update(['payment_status' => 'cancelled']);
        }

        return response(['message' => 'OK']);
    }
}