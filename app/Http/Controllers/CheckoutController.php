<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\UserAddress;
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

        $shippingFee = 15000;
        $total = $subtotal + $shippingFee;

        $defaultAddress = UserAddress::where('user_id', Auth::id())
            ->with(['province', 'city', 'district']) 
            ->latest()
            ->first();

        return Inertia::render('Checkout/Index', [
            'items' => $checkoutItems,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'isDirectPurchase' => !$request->has('from_cart'),
            'user_address' => $defaultAddress,
            // Kirim Client Key ke Vue agar Popup bisa jalan
            'midtrans_client_key' => config('midtrans.client_key'), 
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
            'shipping_method' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $shippingCost = ($request->shipping_method === 'express') ? 30000 : 15000;

            $order = Order::create([
                'user_id' => Auth::id(), 
                'shipping_address_id' => $request->shipping_address_id,
                'order_number' => 'ORD-' . time() . rand(1000, 9999),
                'subtotal' => 0,
                'shipping_cost' => $shippingCost,
                'total_amount' => 0,
                'payment_method' => $request->payment_method,
                'payment_status' => 'unpaid',
                'order_status' => 'pending',
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->find($item['variant_id']);
                if (!$variant) continue;

                $price = $variant->price;
                $subtotalItem = $price * $item['quantity'];
                $totalAmount += $subtotalItem;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name ?? 'Product',
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $subtotalItem,
                ]);
            }

            $order->update([
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount + $shippingCost
            ]);

            if ($request->payment_method === 'online_payment') {
                
                // Menggunakan Config dari .env (Pastikan .env Anda sudah benar)
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
                    ],
                ];

                // ✅ KEMBALI KE getSnapToken (POPUP)
                $snapToken = Snap::getSnapToken($params);
                
                $order->snap_token = $snapToken;
                $order->save();

                DB::commit();
                
                // ✅ Kirim Token ke Frontend
                return back()->with('snap_token', $snapToken);
            }

            DB::commit();
            return to_route('checkout.success')->with('toast_success', 'Order placed via COD!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('toast_error', 'Failed: ' . $e->getMessage());
        }
    }

    public function success(): Response
    {
        return Inertia::render('Checkout/OrderSuccess');
    }
}