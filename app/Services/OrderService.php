<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\UserReward;
use App\Models\OrderCancellation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected PaymentService $paymentService;
    protected FcmService $fcmService;
    protected PointService $pointService;       // [BARU]
    protected MembershipService $membershipService;

    public function __construct(
        PaymentService $paymentService, 
        FcmService $fcmService, 
        PointService $pointService,
        MembershipService $membershipService
    )
    {
        $this->paymentService = $paymentService;
        $this->fcmService = $fcmService;
        $this->pointService = $pointService;
        $this->membershipService = $membershipService;
    }

    public function createOrder(User $user, array $data): Order
    {
        if ($user->is_banned) {
            throw new Exception("Your account is currently restricted.");
        }

        return DB::transaction(function () use ($user, $data) {
            
            // 1. Calculate Temporary Subtotal
            $tempSubtotal = 0;
            foreach ($data['items'] as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                if ($variant) {
                    $price = $variant->final_price ?? $variant->price;
                    $tempSubtotal += $price * $item['quantity'];
                }
            }

            // 2. Voucher Logic & Validation (DIPERBAIKI)
            $discountAmount = 0;
            $usedVoucher = null; // Default null

            if (!empty($data['voucher_code'])) {
                // Cari voucher
                $voucherCandidate = UserReward::where('code', $data['voucher_code'])
                    ->where('user_id', $user->id)
                    ->where('is_used', false)
                    ->with('reward')
                    ->lockForUpdate()
                    ->first();

                // [FIX 1] Validasi Tegas: Jika voucher ada tapi tidak valid, lempar ERROR.
                // Jangan biarkan user checkout kalau dia berharap dapat diskon tapi gagal.
                if (!$voucherCandidate) {
                    throw new Exception("Voucher tidak valid atau sudah digunakan.");
                }

                // Cek Minimal Belanja
                if ($tempSubtotal < $voucherCandidate->reward->min_spend) {
                     throw new Exception("Total belanja kurang dari syarat minimum voucher (Min: " . number_format($voucherCandidate->reward->min_spend) . ")");
                }

                // Jika lolos validasi, baru set variable $usedVoucher
                $usedVoucher = $voucherCandidate;

                // Hitung Nominal
                if ($usedVoucher->reward->type === 'discount_fixed') {
                    $discountAmount = $usedVoucher->reward->value;
                } elseif ($usedVoucher->reward->type === 'discount_percent') {
                    $discountAmount = ($tempSubtotal * $usedVoucher->reward->value) / 100;
                }

                // [FIX 2] Capping Diskon (Best Practice)
                // Diskon Barang TIDAK BOLEH memotong Ongkir. 
                // Maksimal diskon = Total Harga Barang.
                $discountAmount = min($discountAmount, $tempSubtotal);
            }

            // 3. Create Order Header
            $order = Order::create([
                'user_id' => $user->id,
                'shipping_address_id' => $data['shipping_address_id'],
                'order_number' => 'ORD-' . time() . rand(1000, 9999),
                'subtotal' => 0, 
                'shipping_cost' => $data['shipping_cost'],
                'shipping_courier' => $data['shipping_courier'],
                'discount_amount' => $discountAmount,
                'voucher_code' => ($usedVoucher ? $data['voucher_code'] : null), // Hanya simpan kode jika valid
                'total_amount' => 0, 
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'order_status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            // 4. Process Items (LOCKING & STOCK DEDUCTION)
            $realSubtotal = 0;

            foreach ($data['items'] as $item) {
                $variant = ProductVariant::where('id', $item['variant_id'])->lockForUpdate()->first();

                if (!$variant || $variant->stock < $item['quantity']) {
                    throw new Exception("Out of stock for product: " . ($variant->product->name ?? 'Unknown'));
                }

                $variant->decrement('stock', $item['quantity']);

                $fixPrice = $variant->final_price ?? $variant->price;
                $lineTotal = $fixPrice * $item['quantity'];
                $realSubtotal += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'variant_name' => $variant->volume,
                    'product_name' => $variant->product->name,
                    'quantity' => $item['quantity'],
                    'price' => $fixPrice,
                    'subtotal' => $lineTotal,
                ]);
            }

            // 5. Final Calculation
            // Karena $discountAmount sudah di-cap di atas (tidak lebih dari subtotal),
            // Rumus ini aman & ongkir tidak ikut terpotong.
            $grandTotal = ($realSubtotal - $discountAmount) + $data['shipping_cost'];
            
            // Safety net terakhir (tidak boleh negatif)
            if ($grandTotal < 0) $grandTotal = 0;

            $order->update([
                'subtotal' => $realSubtotal,
                'total_amount' => $grandTotal
            ]);

            // [FIX 3] Hanya update voucher jika BENAR-BENAR TERPAKAI & VALID
            if ($usedVoucher) {
                $usedVoucher->update(['is_used' => true]);
            }

            // 6. Generate Snap Token
            if ($data['payment_method'] === 'online_payment' && $grandTotal > 0) {
                $snapToken = $this->paymentService->createSnapToken($order, $user);
                $order->update(['snap_token' => $snapToken]);
            }

            return $order;
        });
    }

    /**
     * Handle Order Cancellation Logic (User Side)
     */
    public function cancelOrder(User $user, int $orderId, string $reason): array
    {
        return DB::transaction(function () use ($user, $orderId, $reason) {
            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Gunakan Constants agar konsisten
            if (!in_array($order->order_status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING, 'paid'])) { // Sesuaikan jika ada status 'paid' khusus
                throw new Exception("Order cannot be canceled at this stage.");
            }

            // 1. Catat di tabel OrderCancellation
            // Kita pakai updateOrCreate: Jika user pernah request lalu ditolak, dan request lagi, record lama diupdate.
            $cancellation = OrderCancellation::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'reason' => $reason, 
                    'status' => 'pending',
                    // Reset admin note jika user mengajukan ulang
                    'admin_note' => null 
                ]
            );

            // Skenario A: Masih Pending (Belum diproses gudang) -> Auto Cancel
            if ($order->order_status === Order::STATUS_PENDING) {
                
                // Update Order Status
                $order->update(['order_status' => Order::STATUS_CANCELLED]);
                
                // Auto Approve di tabel cancellation
                $cancellation->update(['status' => 'approved']);

                // Kembalikan Stok
                foreach ($order->items as $item) {
                    if ($item->productVariant) {
                        $item->productVariant->increment('stock', $item->quantity);
                    }
                }

                // [TODO]: Jika sistem punya Wallet/Saldo, kembalikan saldo di sini (jika status paid)
                // if ($order->payment_status === Order::PAYMENT_PAID) {
                //      $this->walletService->refund($user, $order->total_amount);
                // }

                $this->fcmService->sendToUser(
                    $user->id,
                    "Order Canceled ✅",
                    "Order #{$order->order_number} has been successfully canceled.",
                    "/orders"
                );
                
                return ['status' => 'canceled', 'message' => 'Order successfully canceled.'];
            }
            
            // Skenario B: Sudah Dibayar/Proses -> Butuh Approval Admin
            else {
                $order->update(['order_status' => 'cancellation_requested']);
                
                $this->fcmService->sendToUser(
                    $user->id,
                    "Cancellation Requested ⏳",
                    "Request submitted. Waiting for admin approval.",
                    "/orders"
                );

                return ['status' => 'requested', 'message' => 'Cancellation request submitted. Waiting for admin approval.'];
            }
        });
    }
    /**
     * Handle Logic when Payment is Successful
     */
    public function processPaymentSuccess(Order $order): void
    {
        // Idempotency: If already paid, do nothing (prevent double reward)
        if ($order->payment_status === 'paid') {
            return;
        }

        DB::transaction(function () use ($order) {
            // 1. Update Status
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing'
            ]);

            // 2. Grant Rewards (Points & Membership)
            // We wrap this in try-catch inside grantRewards to prevent 
            // blocking the order if point system fails.
            $this->grantRewards($order);

            // 3. Send Success Notification
            $this->fcmService->sendToUser(
                $order->user_id,
                "Payment Confirmed! 🎉",
                "Order #{$order->order_number} is now being processed.",
                "/orders"
            );
            
            Log::info("Payment success processed for Order #{$order->order_number}");
        });
    }

    /**
     * Handle Logic when Payment Failed/Expired
     */
    public function processPaymentFailure(Order $order): void
    {
        // Idempotency: If already failed, do nothing
        if (in_array($order->payment_status, ['failed', 'expired', 'canceled'])) {
            return;
        }

        DB::transaction(function () use ($order) {
            // 1. Update Status
            $order->update([
                'payment_status' => 'failed',
                'order_status' => 'canceled'
            ]);

            // 2. Restore Stock (Increment stock back)
            foreach ($order->items as $item) {
                if ($item->productVariant) {
                    $item->productVariant->increment('stock', $item->quantity);
                }
            }

            // 3. Send Failed Notification
            $this->fcmService->sendToUser(
                $order->user_id,
                "Payment Failed ❌",
                "Order #{$order->order_number} was canceled due to payment failure.",
                "/orders"
            );

            Log::info("Payment failed processed for Order #{$order->order_number}, Stock restored.");
        });
    }

    /**
     * Helper to Calculate and Give Points
     */
    private function grantRewards(Order $order): void
    {
        try {
            // Logic to calculate points (e.g., 1 point per 10k)
            $points = (int) floor($order->subtotal / 10000);

            if ($points > 0) {
                // Assuming you have PointService injected
                $this->pointService->addPoints(
                    $order->user,
                    $points,
                    'purchase',
                    "Reward for Order #{$order->order_number}"
                );
            }

            // Check Membership Upgrade
            // Assuming you have MembershipService injected
            $this->membershipService->checkAndUpgradeLevel($order->user, $order->subtotal);

        } catch (\Exception $e) {
            Log::error("Reward Granting Error: " . $e->getMessage());
        }
    }
}