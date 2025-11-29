<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    protected ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Ambil data keranjang, TAPI sinkronkan dengan data terbaru dari DB.
     * Agar harga dan stok selalu real-time saat user buka halaman Cart.
     */
    public function getCart(): array
    {
        $cart = Session::get('cart', []);
        $freshCart = [];
        $hasChanges = false;

        foreach ($cart as $variantId => $item) {
            // Ambil data fresh dari DB (bisa dicache via repository/service jika mau)
            // Di sini kita pakai findByIdWithVariants dari repository sebelumnya
            // TAPI, kita butuh spesifik Variant. Mari kita asumsikan akses DB langsung via Model 
            // atau tambahkan method di Repo. Disini saya pakai Model langsung untuk ringkas.
            $variant = \App\Models\ProductVariant::with('product')->find($variantId);

            // Jika barang sudah dihapus admin, hapus dari keranjang
            if (!$variant) {
                $hasChanges = true;
                continue;
            }

            // Update harga & stok terbaru (PENTING)
            $item['price'] = $variant->price;
            $item['stock'] = $variant->stock; // Info stok real-time
            $item['name'] = $variant->product->name . ' (' . $variant->volume . ')';
            $item['image_url'] = $variant->product->image_url;
            
            // Validasi: Jika stok tiba-tiba 0 atau kurang dari qty cart
            if ($variant->stock <= 0) {
                // Opsi: Hapus atau tandai sold out
                $item['error'] = 'Stok habis';
            } elseif ($item['quantity'] > $variant->stock) {
                $item['quantity'] = $variant->stock; // Turunkan paksa ke max stok
                $item['error'] = 'Stok terbatas, jumlah disesuaikan';
                $hasChanges = true;
            }

            $freshCart[$variantId] = $item;
        }

        if ($hasChanges) {
            Session::put('cart', $freshCart);
        }

        return $freshCart;
    }

    public function addToCart(int $variantId, int $quantity): void
    {
        $variant = \App\Models\ProductVariant::with('product')->find($variantId);

        if (!$variant) {
            throw ValidationException::withMessages(['variant_id' => 'Product not found.']);
        }

        $cart = Session::get('cart', []);
        $currentQty = isset($cart[$variantId]) ? $cart[$variantId]['quantity'] : 0;
        $newQty = $currentQty + $quantity;

        // 1. CEK STOK SEBELUM NAMBAH (CRITICAL)
        if ($newQty > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Stok tidak cukup. Tersisa: {$variant->stock}"
            ]);
        }

        $cart[$variantId] = [
            'variant_id' => $variant->id,
            'product_id' => $variant->product->id,
            'product_slug' => $variant->product->slug,
            'name' => $variant->product->name . ' (' . $variant->volume . ')', 
            'quantity' => $newQty,
            'price' => $variant->price, // Harga saat add (nanti di-refresh di getCart)
            'image_url' => $variant->product->image_url, 
            'stock' => $variant->stock
        ];

        Session::put('cart', $cart);
    }

    public function updateQuantity(int $variantId, int $quantity): void
    {
        $cart = Session::get('cart', []);

        if (!isset($cart[$variantId])) return;

        // Cek Stok lagi
        $variant = \App\Models\ProductVariant::find($variantId);
        if ($variant && $quantity > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Maksimal pembelian adalah {$variant->stock}"
            ]);
        }

        $cart[$variantId]['quantity'] = $quantity;
        Session::put('cart', $cart);
    }

    public function removeFromCart(int $variantId): void
    {
        $cart = Session::get('cart', []);
        if (isset($cart[$variantId])) {
            unset($cart[$variantId]);
            Session::put('cart', $cart);
        }
    }
}