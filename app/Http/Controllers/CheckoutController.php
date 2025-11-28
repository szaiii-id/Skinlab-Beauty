<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\UserReward; // --- TAMBAHAN BARU ---
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $items = $request->input('items', []);
        $quantity = $request->input('quantity', 1);
        
        if (empty($items)) return redirect()->back()->with('toast_error', 'No items selected.');

        $checkoutItems = [];
        $subtotal = 0;

        foreach ($items as $variantId) {
            $variant = ProductVariant::with('product')->find($variantId);
            if ($variant && $variant->stock > 0) {
                $itemQuantity = is_array($quantity) ? ($quantity[$variantId] ?? 1) : $quantity;
                $checkoutItems[] = [
                    'variant_id' => $variant->id,
                    'name' => $variant->product->name . ' (' . $variant->volume . ')',
                    'price' => $variant->price,
                    'quantity' => $itemQuantity,
                    'image_url' => $variant->product->image_url,
                    'stock' => $variant->stock,
                    'volume' => $variant->volume,
                ];
                $subtotal += $variant->price * $itemQuantity;
            }
        }

        if (empty($checkoutItems)) return redirect()->back()->with('toast_error', 'Items unavailable.');

        $shippingFee = 0; // Default 0, nanti dihitung di frontend via API
        $total = $subtotal;

        $defaultAddress = UserAddress::where('user_id', Auth::id())
            ->with(['province', 'city', 'district']) 
            ->latest()
            ->first();

        // --- REWARD LOGIC: AMBIL VOUCHER USER YANG AKTIF ---
        $availableVouchers = UserReward::where('user_id', Auth::id())
            ->where('is_used', false)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('reward') // Eager load detail reward (nilai diskon, min spend)
            ->get()
            ->filter(function($userReward) use ($subtotal) {
                // Filter di PHP: Hanya ambil voucher yang memenuhi syarat Min Spend
                return $subtotal >= ($userReward->reward->min_spend ?? 0);
            })
            ->values(); // Reset index array
        // ---------------------------------------------------

        return Inertia::render('Checkout/Index', [
            'items' => $checkoutItems,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'isDirectPurchase' => !$request->has('from_cart'),
            'user_address' => $defaultAddress,
            'midtrans_client_key' => config('midtrans.client_key'),
            
            // Kirim ke Frontend
            'available_vouchers' => $availableVouchers 
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:user_addresses,id',
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'shipping_cost' => 'required|numeric', 
            'shipping_courier' => 'required|string',
            'voucher_code' => 'nullable|string|exists:user_rewards,code' // Validasi kode voucher
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $subtotalCheck = 0; // Untuk validasi ulang voucher
            $shippingCost = $request->shipping_cost; 
            
            // 1. Hitung Subtotal Real dulu (Keamanan)
            foreach ($request->items as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                if ($variant) {
                    $subtotalCheck += $variant->price * $item['quantity'];
                }
            }

            // --- REWARD LOGIC: HITUNG DISKON ---
            $discountAmount = 0;
            $usedVoucher = null;

            if ($request->voucher_code) {
                // Cari Voucher
                $userReward = UserReward::where('code', $request->voucher_code)
                    ->where('user_id', Auth::id())
                    ->where('is_used', false)
                    ->with('reward')
                    ->first();

                if ($userReward) {
                    // Validasi Min Spend lagi (Backend validation is a must)
                    if ($subtotalCheck >= $userReward->reward->min_spend) {
                        
                        if ($userReward->reward->type === 'discount_fixed') {
                            $discountAmount = $userReward->reward->value;
                        } elseif ($userReward->reward->type === 'discount_percent') {
                            $discountAmount = ($subtotalCheck * $userReward->reward->value) / 100;
                            // Opsional: Cek max discount jika ada
                        }
                        
                        // Tandai voucher akan dipakai
                        $usedVoucher = $userReward;
                    }
                }
            }
            // -----------------------------------

            $order = Order::create([
                'user_id' => Auth::id(), 
                'shipping_address_id' => $request->shipping_address_id,
                'order_number' => 'ORD-' . time() . rand(1000, 9999),
                'subtotal' => 0, // Nanti diupdate
                'shipping_cost' => $shippingCost,
                'shipping_courier' => $request->shipping_courier, 
                'discount_amount' => $discountAmount, // Simpan Diskon
                'voucher_code' => $request->voucher_code, // Simpan Kode
                'total_amount' => 0, // Nanti diupdate
                'payment_method' => $request->payment_method,
                'payment_status' => 'unpaid',
                'order_status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Insert Items (Sama seperti sebelumnya)
            $calculatedSubtotal = 0;
            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->find($item['variant_id']);
                if (!$variant) continue;

                $price = $variant->price;
                $subItemTotal = $price * $item['quantity'];
                $calculatedSubtotal += $subItemTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name ?? 'Product',
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $subItemTotal,
                ]);
            }

            // Hitung Grand Total Akhir
            // Rumus: Subtotal + Ongkir - Diskon
            $grandTotal = ($calculatedSubtotal + $shippingCost) - $discountAmount;
            
            // Safety: Total tidak boleh minus
            if ($grandTotal < 0) $grandTotal = 0;

            $order->update([
                'subtotal' => $calculatedSubtotal,
                'total_amount' => $grandTotal
            ]);

            // --- REWARD LOGIC: TANDAI VOUCHER TERPAKAI ---
            if ($usedVoucher) {
                $usedVoucher->update([
                    'is_used' => true,
                    // Opsional: Simpan tanggal dipakai
                ]);
            }
            // ---------------------------------------------

            if ($request->payment_method === 'online_payment' && $grandTotal > 0) {
                
                Config::$serverKey = config('midtrans.server_key');
                Config::$isProduction = config('midtrans.is_production');
                Config::$isSanitized = config('midtrans.is_sanitized');
                Config::$is3ds = config('midtrans.is_3ds');

                $user = Auth::user();

                $params = [
                    'transaction_details' => [
                        'order_id' => $order->order_number,
                        'gross_amount' => (int) ceil($order->total_amount),
                    ],
                    'customer_details' => [
                        'first_name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone_number ?? '', 
                    ],
                    // Item details opsional (Midtrans kadang strict soal total match item)
                ];

                $snapToken = Snap::getSnapToken($params);
                
                $order->snap_token = $snapToken;
                $order->save();

                DB::commit();
                return back()->with('snap_token', $snapToken);
            }

            DB::commit();
            
            // Jika total 0 (misal voucher 100%), langsung success tanpa midtrans
            return to_route('checkout.success')->with('toast_success', 'Order placed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Checkout Error: " . $e->getMessage());
            return back()->with('toast_error', 'Failed: ' . $e->getMessage());
        }
    }

    public function success(): Response
    {
        return Inertia::render('Checkout/OrderSuccess');
    }
}