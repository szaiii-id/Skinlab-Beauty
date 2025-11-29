<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    protected OrderService $orderService;

    /**
     * Inject OrderService.
     * The controller delegates all business logic (Points, Stock, etc.) to this service.
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Handle Incoming Webhook from Midtrans
     */
    public function handle(Request $request)
    {
        // 1. Setup Midtrans Configuration
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        try {
            // 2. Parse Notification Instance
            $notification = new Notification();
        } catch (\Exception $e) {
            Log::error("Midtrans Notification Error: " . $e->getMessage());
            return response(['message' => 'Invalid notification signature'], 400);
        }

        // 3. Extract Data
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $orderId = $notification->order_id;
        $fraud = $notification->fraud_status;

        // 4. Find Order
        // We use eager loading 'items.productVariant' because if payment failed, 
        // the Service needs this data to restore stock.
        $order = Order::with(['items.productVariant', 'user'])
            ->where('order_number', $orderId)
            ->first();

        if (!$order) {
            Log::warning("Callback received for unknown Order ID: {$orderId}");
            return response(['message' => 'Order not found'], 404);
        }

        Log::info("Midtrans Callback: Order #{$orderId} Status: [{$status}]");

        try {
            // 5. Determine Logic based on Status
            if ($status == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                        $order->update(['payment_status' => 'pending']);
                    } else {
                        // SUCCESS: Credit Card
                        $this->orderService->processPaymentSuccess($order);
                    }
                }
            } 
            elseif ($status == 'settlement') {
                // SUCCESS: Bank Transfer, GoPay, QRIS, etc.
                $this->orderService->processPaymentSuccess($order);
            } 
            elseif ($status == 'pending') {
                // PENDING: Waiting for payment
                $order->update(['payment_status' => 'pending']);
            } 
            elseif ($status == 'deny' || $status == 'expire' || $status == 'cancel') {
                // FAILED: Payment failed or expired
                $this->orderService->processPaymentFailure($order);
            }

            return response(['message' => 'OK']);

        } catch (\Exception $e) {
            Log::error("Error processing callback for Order #{$orderId}: " . $e->getMessage());
            return response(['message' => 'Internal Server Error'], 500);
        }
    }
}