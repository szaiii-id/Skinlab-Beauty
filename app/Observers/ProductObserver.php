<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    // Tag ini adalah kunci utama agar kita bisa menghapus sekelompok cache sekaligus
    private const CACHE_TAG_PRODUCTS = 'products';

    public $afterCommit = true;

    public function saved(Product $product): void
    {
        $this->clearProductCache($product);
    }

    public function deleted(Product $product): void
    {
        $this->clearProductCache($product);
    }

    private function clearProductCache(Product $product): void
    {
        // 1. Hapus cache detail produk spesifik ini
        Cache::tags([self::CACHE_TAG_PRODUCTS])->forget("product:detail:{$product->id}");

        // 2. Hapus semua halaman list (Kategori, Brand, Index) 
        // Ini penting agar jika harga/nama berubah, list di halaman depan langsung update
        Cache::tags([self::CACHE_TAG_PRODUCTS])->flush();
    }
}