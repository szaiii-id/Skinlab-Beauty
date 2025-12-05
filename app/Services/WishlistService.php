<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    /**
     * Mengambil data Wishlist dari Session.
     * TAPI, kita refresh datanya dari DB agar User tidak melihat harga/stok basi.
     */
    public function getWishlist(): array
    {
        $wishlistSession = Session::get('wishlist', []);
        
        if (empty($wishlistSession)) return [];

        $variantIds = array_keys($wishlistSession);

        // Ambil data fresh dari DB beserta relasi lengkap
        $freshVariants = ProductVariant::with(['product.brand', 'product.category', 'promoBanners'])
            ->whereIn('id', $variantIds)
            ->get();

        $freshWishlist = [];

        foreach ($freshVariants as $variant) {
            $freshWishlist[$variant->id] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'product_slug' => $variant->product->slug,
                'name' => $variant->product->name, // Nama Produk Asli
                'volume' => $variant->volume,
                
                // DATA VITAL UNTUK PRODUCT CARD:
                'price' => $variant->price,
                'final_price' => $variant->final_price, // Ambil dari Accessor Model
                'discount_info' => $variant->discount_info, // Ambil dari Accessor Model
                'image_url' => $variant->product->thumbnail,
                'stock' => $variant->stock,
                
                // INFO TAMBAHAN:
                'brand_name' => $variant->product->brand->name ?? '',
                'category_name' => $variant->product->category->name ?? '',
                'tags' => $variant->product->suitability_tags,
                
                'added_at' => $wishlistSession[$variant->id]['added_at'] ?? now(),
            ];
        }

        // Cleanup session jika ada item yang dihapus dari DB
        if (count($wishlistSession) !== count($freshWishlist)) {
            Session::put('wishlist', $freshWishlist);
        }

        return $freshWishlist;
    }

    public function addToWishlist(int $variantId): void
    {
        $wishlist = Session::get('wishlist', []);

        if (isset($wishlist[$variantId])) {
            throw ValidationException::withMessages(['product' => 'Produk sudah ada di Wishlist Anda.']);
        }

        // Cek keberadaan produk (Validasi ringan)
        $variant = ProductVariant::with('product')->find($variantId);

        if (!$variant) {
            throw ValidationException::withMessages(['product' => 'Produk tidak ditemukan.']);
        }

        // Simpan ke Session
        $wishlist[$variantId] = [
            'variant_id' => $variant->id,
            'added_at' => now(), // Kita simpan timestamp sekedar info
            // Data lain sebenarnya tidak perlu disimpan lengkap di session 
            // karena akan di-load ulang di getWishlist(), tapi untuk backup oke saja.
        ];

        Session::put('wishlist', $wishlist);
    }

    public function removeFromWishlist(int $variantId): void
    {
        $wishlist = Session::get('wishlist', []);

        if (isset($wishlist[$variantId])) {
            unset($wishlist[$variantId]);
            Session::put('wishlist', $wishlist);
        }
    }

    public function getCount(): int
    {
        return count(Session::get('wishlist', []));
    }
}