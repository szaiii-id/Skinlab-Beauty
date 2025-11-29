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
        // 1. Ambil list ID variant dari session
        // Format Session: ['variant_id_1' => [...data...], 'variant_id_2' => [...]]
        $wishlistSession = Session::get('wishlist', []);
        
        if (empty($wishlistSession)) {
            return [];
        }

        $variantIds = array_keys($wishlistSession);

        // 2. Ambil data segar dari DB (Hanya 1 Query ringan via whereIn)
        $freshVariants = ProductVariant::with('product')
            ->whereIn('id', $variantIds)
            ->get();

        $freshWishlist = [];
        $idsFound = [];

        foreach ($freshVariants as $variant) {
            $idsFound[] = $variant->id;
            
            // Re-construct data agar selalu update (Stok & Harga real-time)
            $freshWishlist[$variant->id] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'product_slug' => $variant->product->slug,
                'name' => $variant->product->name . ' (' . $variant->volume . ')',
                'price' => $variant->price,
                'image_url' => $variant->product->image_url,
                'stock' => $variant->stock, // Penting untuk UI (misal: disable tombol add to cart jika 0)
                'volume' => $variant->volume,
                'added_at' => $wishlistSession[$variant->id]['added_at'] ?? now(), // Pertahankan waktu add
            ];
        }

        // 3. Self-Healing: Jika ada produk di session tapi tidak ada di DB (dihapus admin),
        // otomatis dia hilang dari $freshWishlist karena loop di atas based on DB result.
        // Kita update sessionnya agar bersih.
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