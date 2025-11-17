<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja
     */
    public function index(Request $request): Response
    {
        $cart = $request->session()->get('cart', []);
        
        return Inertia::render('Cart/Index', [
            'cart' => $cart
        ]);
    }

    /**
     * Menyimpan item ke keranjang
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::with('product')->find($request->variant_id);
        $cart = $request->session()->get('cart', []);

        if (isset($cart[$variant->id])) {
            $cart[$variant->id]['quantity'] += $request->quantity;
        } else {
            $cart[$variant->id] = [
                'name' => $variant->product->name . ' (' . $variant->volume . ')', 
                'quantity' => (int)$request->quantity,
                'price' => $variant->price,
                'image_url' => $variant->product->image_url, 
                'product_slug' => $variant->product->slug,
                'product_id' => $variant->product->id,
                'variant_id' => $variant->id,
            ];
        }

        $request->session()->put('cart', $cart);

        return redirect()->back()->with('toast_success', 'Product successfully added to cart!');
    }

    /**
     * Update kuantitas
     */
    public function update(Request $request, $variantId): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $request->session()->get('cart', []);

        if (isset($cart[$variantId])) {
            $cart[$variantId]['quantity'] = (int)$request->quantity;
            $request->session()->put('cart', $cart);
        }

        return redirect()->back()->with('toast_success', 'Cart updated successfully!');
    }

    /**
     * Menghapus item dari keranjang
     */
    public function destroy(Request $request, $variantId): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);

        if (isset($cart[$variantId])) {
            unset($cart[$variantId]);
            $request->session()->put('cart', $cart);
        }

        return redirect()->back()->with('toast_success', 'Item removed from cart.');
    }
}