<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja (Method ini sudah benar).
     */
    public function index(Request $request): Response
    {
        $cart = $request->session()->get('cart', []);
        
        return Inertia::render('Cart/Index', [
            'cart' => $cart
        ]);
    }

    /**
     * PERUBAHAN 1:
     * Menyimpan item (dengan data tambahan) ke keranjang (Session).
     */
    public function store(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Ambil relasi 'product' agar kita bisa mengakses slug dan image
        $variant = ProductVariant::with('product')->find($request->variant_id);

        $cart = $request->session()->get('cart', []);

        if (isset($cart[$variant->id])) {
            $cart[$variant->id]['quantity'] += $request->quantity;
        } else {
            $cart[$variant->id] = [
                // Kita tambahkan spasi agar lebih rapi
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
     * PERUBAHAN 2:
     * Method baru untuk update kuantitas (dari tombol +/-).
     */
    public function update(Request $request, $variantId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $request->session()->get('cart', []);

        if (isset($cart[$variantId])) {
            $cart[$variantId]['quantity'] = (int)$request->quantity;
            $request->session()->put('cart', $cart);
        }

        // Kita kirim pesan sukses agar Pop-up/Modal tahu
        return redirect()->back()->with('toast_success', 'Cart updated successfully!');
    }

    /**
     * PERUBAHAN 3:
     * Method baru untuk menghapus item dari keranjang.
     */
    public function destroy(Request $request, $variantId)
    {
        $cart = $request->session()->get('cart', []);

        if (isset($cart[$variantId])) {
            unset($cart[$variantId]); // Hapus item
            $request->session()->put('cart', $cart);
        }

        return redirect()->back()->with('toast_success', 'Item removed from cart.');
    }
}