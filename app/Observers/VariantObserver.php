<?php

namespace App\Observers;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;

class VariantObserver
{
    private const CACHE_TAG_PRODUCTS = 'products';

    public $afterCommit = true;

    public function saved(ProductVariant $variant): void
    {
        $this->clearParentCache($variant);
    }

    public function deleted(ProductVariant $variant): void
    {
        $this->clearParentCache($variant);
    }

    private function clearParentCache(ProductVariant $variant): void
    {
        // Karena stok ada di variant, kita harus mencari ID parent product-nya
        // Lalu hapus cache detail produk tersebut agar user tidak melihat data stok 'basi'
        if ($variant->product_id) {
            Cache::tags([self::CACHE_TAG_PRODUCTS])->forget("product:detail:{$variant->product_id}");
        }

        // Kita juga flush list global.
        // Contoh kasus: Stok habis (0). Maka produk harus otomatis ada label "Sold Out" di halaman list.
        Cache::tags([self::CACHE_TAG_PRODUCTS])->flush();
    }
}