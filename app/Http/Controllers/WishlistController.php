<?php

namespace App\Http\Controllers;

use App\Services\WishlistService;
use App\Services\CartService; // Reuse logic Cart
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class WishlistController extends Controller
{
    protected WishlistService $wishlistService;
    protected CartService $cartService;

    public function __construct(WishlistService $wishlistService, CartService $cartService)
    {
        $this->wishlistService = $wishlistService;
        $this->cartService = $cartService;
    }

    public function index(): Response
    {
        // Data diambil fresh dari DB via Service
        $wishlist = $this->wishlistService->getWishlist();
        
        return Inertia::render('Wishlist/Index', [
            // array_values agar di Javascript terbaca sebagai Array [], bukan Object {}
            'wishlist' => array_values($wishlist) 
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_variant_id' => 'required|integer',
        ]);

        try {
            $this->wishlistService->addToWishlist($request->product_variant_id);
            return back()->with('toast_success', 'Berhasil ditambahkan ke Wishlist!');
            
        } catch (ValidationException $e) {
            return back()->with('toast_error', $e->getMessage());
        }
    }

    public function destroy($variantId): RedirectResponse
    {
        $this->wishlistService->removeFromWishlist($variantId);
        return back()->with('toast_success', 'Dihapus dari Wishlist.');
    }

    /**
     * Fitur Move to Cart (Gabungan Logic)
     */
    public function moveToCart(Request $request, $variantId): RedirectResponse
    {
        try {
            // 1. Coba masukkan ke Cart dulu (CartService akan cek Stok real-time)
            // Jika stok habis, CartService akan throw Exception, code berhenti disini.
            $this->cartService->addToCart($variantId, 1);

            // 2. Jika sukses masuk cart (tidak error), baru hapus dari wishlist
            $this->wishlistService->removeFromWishlist($variantId);

            return back()->with('toast_success', 'Produk berhasil dipindahkan ke Keranjang!');

        } catch (ValidationException $e) {
            // Tangkap error stok habis dari CartService
            return back()->with('toast_error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('toast_error', 'Gagal memindahkan produk.');
        }
    }

    /**
     * Endpoint untuk Navbar (Cek jumlah wishlist)
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'wishlist_count' => $this->wishlistService->getCount()
        ]);
    }
}