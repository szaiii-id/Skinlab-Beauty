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
            $variant = ProductVariant::with('product')->find($variantId);
            
            // Skip if product not found or out of stock
            if ($variant && $variant->stock > 0) {
                // Determine quantity (handle both single int or array of qtys)
                $itemQuantity = is_array($quantities) ? ($quantities[$variantId] ?? 1) : $quantities;
                
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
    public function store(Request $request): RedirectResponse
    {
        // 1. Validate Input
        $validatedData = $request->validate([
            'shipping_address_id' => 'required|exists:user_addresses,id',
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'shipping_cost' => 'required|numeric|min:0', 
            'shipping_courier' => 'required|string',
            'voucher_code' => 'nullable|string|exists:user_rewards,code',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // 2. Execute Order via Service (Transaction, Stock Lock, Payment)
            $order = $this->orderService->createOrder(Auth::user(), $validatedData);

            // 3. Handle Online Payment (Midtrans Snap)
            if ($order->snap_token) {
                // Return back with snap_token to trigger popup on frontend
                return back()->with('snap_token', $order->snap_token);
            }

            // 4. Handle Offline/Manual Payment (Success Redirect)
            return to_route('checkout.success')->with('toast_success', 'Order placed successfully!');

        } catch (\Exception $e) {
            // 5. Handle Errors (Stockout, Voucher Invalid, etc.)
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