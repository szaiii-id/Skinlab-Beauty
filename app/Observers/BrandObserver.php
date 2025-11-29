<?php

namespace App\Observers;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class BrandObserver
{
    public function saved(Brand $brand): void
    {
        $this->clearCache();
    }

    public function deleted(Brand $brand): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        // 1. Hapus cache list brands dan detail brand
        Cache::tags(['brands'])->flush();

        // 2. PENTING: Hapus cache produk juga.
        // Alasan: Produk menyimpan relasi ke Brand. Jika nama Brand berubah, 
        // cache produk lama mungkin masih menampilkan nama Brand yang lama.
        Cache::tags(['products'])->flush();
    }
}