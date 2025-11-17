<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    /**
     * Display checkout page for both cart and direct purchase
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $items = $request->input('items', []);
        $quantity = $request->input('quantity', 1);
        
        // Jika tidak ada items, redirect back dengan Inertia
        if (empty($items)) {
            return redirect()->back()->with('toast_error', 'No items selected for checkout.');
        }

        $checkoutItems = [];
        $subtotal = 0;
        $shippingFee = 0;
        $total = 0;

        foreach ($items as $variantId) {
            $variant = ProductVariant::with('product')->find($variantId);
            
            if ($variant && $variant->stock > 0) {
                $itemQuantity = $quantity;
                
                $checkoutItems[] = [
                    'variant_id' => $variant->id,
                    'name' => $variant->product->name . ' (' . $variant->volume . ')',
                    'price' => $variant->price,
                    'quantity' => $itemQuantity,
                    'image_url' => $variant->product->image_url,
                    'stock' => $variant->stock,
                    'product_slug' => $variant->product->slug,
                    'volume' => $variant->volume,
                ];
                
                $subtotal += $variant->price * $itemQuantity;
            }
        }

        // Jika tidak ada items valid, redirect back
        if (empty($checkoutItems)) {
            return redirect()->back()->with('toast_error', 'Selected items are no longer available.');
        }

        $shippingFee = 15000;
        $total = $subtotal + $shippingFee;

        return Inertia::render('Checkout/Index', [
            'items' => $checkoutItems,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'isDirectPurchase' => !$request->has('from_cart')
        ]);
    }

    /**
     * Process order
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        // TODO: Implement order creation logic
        // Untuk sekarang, redirect ke success page dengan Inertia
        
        return to_route('checkout.success')->with('toast_success', 'Order placed successfully!');
    }

    /**
     * Success page
     */
    public function success(): Response
    {
        return Inertia::render('Checkout/Success');
    }
}