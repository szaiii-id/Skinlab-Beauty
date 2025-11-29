<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(): Response
    {
        // Panggil getCart dari service agar data disinkronkan dengan DB
        // (Harga terupdate, Stok terupdate)
        $cart = $this->cartService->getCart();
        
        return Inertia::render('Cart/Index', [
            'cart' => $cart
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'variant_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->cartService->addToCart(
                $request->variant_id, 
                $request->quantity
            );
            return back()->with('toast_success', 'Berhasil ditambahkan ke keranjang.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast_error', $e->getMessage());
        }
    }

    public function update(Request $request, $variantId): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->cartService->updateQuantity($variantId, $request->quantity);
            return back()->with('toast_success', 'Keranjang diperbarui.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('toast_error', 'Stok tidak mencukupi.');
        }
    }

    public function destroy($variantId): RedirectResponse
    {
        $this->cartService->removeFromCart($variantId);
        return back()->with('toast_success', 'Item dihapus dari keranjang.');
    }
}