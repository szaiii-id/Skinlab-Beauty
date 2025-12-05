<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Ambil Cart dengan Data LENGKAP (Brand, Category, Diskon, dll)
     */
    public function getCart(): array
    {
        $cartSession = Session::get('cart', []);
        $freshCart = [];
        $hasChanges = false;
        
        if (empty($cartSession)) return [];

        $variantIds = array_keys($cartSession);

        // 1. Ambil Data Fresh dari DB + Relasi (Brand, Category, Promo)
        $variants = ProductVariant::with(['product.brand', 'product.category', 'promoBanners'])
            ->whereIn('id', $variantIds)
            ->get();

        foreach ($variants as $variant) {
            $sessionItem = $cartSession[$variant->id] ?? [];
            $requestedQty = $sessionItem['quantity'] ?? 1;

            // Validasi Stok Real-time
            if ($variant->stock <= 0) {
                // Opsi: Tetap tampilkan tapi tandai error
                $qty = 0; // Atau biarkan, nanti di UI disable checkout
            } elseif ($requestedQty > $variant->stock) {
                $requestedQty = $variant->stock;
                $hasChanges = true;
            }

            // 2. Mapping Data Informatif (Sama seperti Wishlist)
            $freshCart[$variant->id] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'product_slug' => $variant->product->slug,
                'name' => $variant->product->name, // Nama Produk Saja
                'volume' => $variant->volume, // Varian pisah biar rapi
                
                'price' => $variant->price, // Harga Asli
                'final_price' => $variant->final_price, // Harga Diskon (dari Model)
                'discount_info' => $variant->discount_info, // Badge Diskon
                
                'image_url' => $variant->image, // Pakai Accessor (bisa gambar varian/produk)
                'stock' => $variant->stock,
                'quantity' => $requestedQty,
                
                // INFO TAMBAHAN (Biar Keren di UI):
                'brand_name' => $variant->product->brand->name ?? '',
                'category_name' => $variant->product->category->name ?? '',
                'tags' => $variant->product->suitability_tags ?? [],
            ];
        }

        // Hapus item di session jika tidak ada di DB (Product deleted)
        if (count($cartSession) !== count($freshCart) || $hasChanges) {
            // Re-key session dengan data minimal untuk hemat storage
            $sessionToSave = [];
            foreach ($freshCart as $id => $item) {
                $sessionToSave[$id] = [
                    'quantity' => $item['quantity']
                    // Kita tidak simpan harga/nama di session lagi, biar selalu fresh dari DB
                ];
            }
            Session::put('cart', $sessionToSave);
        }

        return $freshCart;
    }

    public function addToCart(int $variantId, int $quantity): void
    {
        // Validasi Stok Sebelum Add
        $variant = ProductVariant::find($variantId);

        if (!$variant) {
            throw ValidationException::withMessages(['product' => 'Product not found.']);
        }

        if ($variant->stock < $quantity) {
            throw ValidationException::withMessages(['quantity' => "Stok tidak cukup. Sisa: {$variant->stock}"]);
        }

        $cart = Session::get('cart', []);
        
        // Cek qty yang sudah ada di cart
        $currentQty = isset($cart[$variantId]) ? $cart[$variantId]['quantity'] : 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $variant->stock) {
             throw ValidationException::withMessages(['quantity' => "Maksimal pembelian {$variant->stock} item."]);
        }

        // Simpan ID & Qty saja di session (Data lain ambil live di getCart)
        $cart[$variantId] = [
            'quantity' => $newQty
        ];

        Session::put('cart', $cart);
    }

    public function updateQuantity(int $variantId, int $quantity): void
    {
        $cart = Session::get('cart', []);
        if (!isset($cart[$variantId])) return;

        $variant = ProductVariant::find($variantId);
        if ($variant && $quantity > $variant->stock) {
            throw ValidationException::withMessages(['quantity' => "Stok maks: {$variant->stock}"]);
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