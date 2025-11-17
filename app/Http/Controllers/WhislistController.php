<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class WhislistController extends Controller
{
    /**
     * Menampilkan halaman wishlist
     */
    public function index(Request $request): Response
    {
        $wishlist = $request->session()->get('wishlist', []);
        
        return Inertia::render('Whislist/Index', [
            'wishlist' => $wishlist
        ]);
    }

    /**
     * Menyimpan item ke wishlist
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
        ]);

        $variant = ProductVariant::with('product')->find($request->product_variant_id);
        $wishlist = $request->session()->get('wishlist', []);

        if (isset($wishlist[$variant->id])) {
            return redirect()->back()->with('toast_error', 'Product is already in your wishlist!');
        }

        $wishlist[$variant->id] = [
            'name' => $variant->product->name . ' (' . $variant->volume . ')', 
            'price' => $variant->price,
            'image_url' => $variant->product->image_url, 
            'product_slug' => $variant->product->slug,
            'product_id' => $variant->product->id,
            'variant_id' => $variant->id,
            'stock' => $variant->stock,
            'volume' => $variant->volume,
        ];

        $request->session()->put('wishlist', $wishlist);

        return redirect()->back()->with('toast_success', 'Product successfully added to wishlist!');
    }

    /**
     * Menghapus item dari wishlist
     */
    public function destroy(Request $request, $variantId): RedirectResponse
    {
        $wishlist = $request->session()->get('wishlist', []);

        if (isset($wishlist[$variantId])) {
            unset($wishlist[$variantId]);
            $request->session()->put('wishlist', $wishlist);
        }

        return redirect()->back()->with('toast_success', 'Item removed from wishlist.');
    }

    /**
     * Memindahkan item dari wishlist ke cart
     */
    public function moveToCart(Request $request, $variantId): RedirectResponse
    {
        $wishlist = $request->session()->get('wishlist', []);
        $cart = $request->session()->get('cart', []);

        if (!isset($wishlist[$variantId])) {
            return redirect()->back()->with('toast_error', 'Item not found in wishlist.');
        }

        $wishlistItem = $wishlist[$variantId];

        if (isset($cart[$variantId])) {
            $cart[$variantId]['quantity'] += 1;
        } else {
            $cart[$variantId] = [
                'name' => $wishlistItem['name'],
                'quantity' => 1,
                'price' => $wishlistItem['price'],
                'image_url' => $wishlistItem['image_url'],
                'product_slug' => $wishlistItem['product_slug'],
                'product_id' => $wishlistItem['product_id'],
                'variant_id' => $wishlistItem['variant_id'],
            ];
        }

        unset($wishlist[$variantId]);

        $request->session()->put('wishlist', $wishlist);
        $request->session()->put('cart', $cart);

        return redirect()->back()->with('toast_success', 'Product moved to cart successfully!');
    }

    /**
     * Get wishlist status (untuk navbar count)
     */
    public function status(Request $request)
    {
        $wishlist = $request->session()->get('wishlist', []);
        
        return response()->json([
            'success' => true,
            'wishlist_items' => array_keys($wishlist),
            'wishlist_count' => count($wishlist)
        ]);
    }
}