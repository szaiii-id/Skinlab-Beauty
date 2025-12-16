<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\UserReward;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    protected OrderService $orderService;

    /**
     * Inject OrderService to handle business logic
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display the checkout page.
     * Prepares data (Items, Address, Vouchers) for the frontend.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        // 1. Parse Items from Request (Usually from Cart or Direct Buy)
        $itemIds = $request->input('items', []);
        $quantities = $request->input('quantity', 1);
        
        if (empty($itemIds)) {
            return redirect()->back()->with('toast_error', 'No items selected.');
        }

        $checkoutItems = [];
        $subtotal = 0;

        // 2. Fetch Product Data
        foreach ($itemIds as $variantId) {
            // Eager Load 'product.brand' agar nama brand muncul
            // Pastikan model ProductVariant Anda memiliki Accessor 'final_price'
            $variant = ProductVariant::with(['product.brand'])->find($variantId);
            
            if ($variant && $variant->stock > 0) {
                $itemQuantity = is_array($quantities) ? ($quantities[$variantId] ?? 1) : $quantities;
                
                // Ambil harga Final (Diskon) dan Harga Asli
                // Asumsi: Model ProductVariant sudah punya getFinalPriceAttribute()
                $finalPrice = $variant->final_price ?? $variant->price; 
                $originalPrice = $variant->price;

                $checkoutItems[] = [
                    'variant_id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'name' => $variant->product->name,
                    'brand' => $variant->product->brand->name ?? '', // Kirim Brand
                    'volume' => $variant->volume,                   // Kirim Volume
                    'image_url' => $variant->product->image_url ?? $variant->image_url,
                    
                    'quantity' => $itemQuantity,
                    
                    // Harga untuk perhitungan
                    'price' => (float) $finalPrice, 
                    'original_price' => (float) $originalPrice,
                    'has_discount' => $finalPrice < $originalPrice,
                    'total_price' => $finalPrice * $itemQuantity, // Total per item
                    
                    'stock' => $variant->stock,
                ];

                // Hitung Subtotal berdasarkan HARGA FINAL (Diskon)
                $subtotal += $finalPrice * $itemQuantity;
            }
        }

        if (empty($checkoutItems)) {
            return redirect()->back()->with('toast_error', 'Selected items are unavailable.');
        }

        // 3. Get Default Shipping Address
        $defaultAddress = UserAddress::where('user_id', Auth::id())
            ->with(['province', 'city', 'district']) 
            ->latest() // Or use ->where('is_default', true) if you have logic for it
            ->first();

        // 4. Get Available Vouchers (Active & Not Expired)
        $availableVouchers = UserReward::where('user_id', Auth::id())
            ->where('is_used', false)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('reward')
            ->get();

        // 5. Render View
        return Inertia::render('Checkout/Index', [
            'items' => $checkoutItems,
            'subtotal' => $subtotal,
            'shipping_fee' => 0, // Calculated on frontend or via API later
            'total' => $subtotal, // Initial total before shipping
            'isDirectPurchase' => !$request->has('from_cart'),
            'user_address' => $defaultAddress,
            'midtrans_client_key' => config('midtrans.client_key'),
            'available_vouchers' => $availableVouchers 
        ]);
    }

    /**
     * Process the order.
     * Validates input and delegates execution to OrderService.
     */
    public function store(Request $request)
    {
        // 1. Standard Validation (Clean & Tidy)
        // Laravel automatically handles empty strings as null, so this is safe.
        $validatedData = $request->validate([
            'shipping_address_id' => 'required|exists:user_addresses,id',
            'items'               => 'required|array',
            'items.*.variant_id'  => 'required|exists:product_variants,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'payment_method'      => 'required|string',
            'shipping_cost'       => 'required|numeric|min:0',
            'shipping_courier'    => 'required|string',
            'notes'               => 'nullable|string|max:500',
            // Restore standard voucher validation
            'voucher_code'        => 'nullable|string|exists:user_rewards,code', 
        ]);

        try {
            // 2. Execute Order
            $order = $this->orderService->createOrder(Auth::user(), $validatedData);

            // 3. Check Midtrans Token (For Frontend Popup)
            if ($order->snap_token) {
                return back()->with('snap_token', $order->snap_token);
            }

            // 4. Success (COD / Manual)
            return to_route('checkout.success')->with('toast_success', 'Order placed successfully!');

        } catch (\Exception $e) {
            // Standard Error Handling (Toast Message)
            return back()->with('toast_error', 'Failed to place order: ' . $e->getMessage());
        }
    }

    /**
     * Display Order Success Page
     */
    public function success(): Response
    {
        return Inertia::render('Checkout/OrderSuccess');
    }
}