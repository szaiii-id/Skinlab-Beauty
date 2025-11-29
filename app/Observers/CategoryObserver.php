<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        $this->clearCache();
    }

    public function deleted(Category $category): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        // Hapus cache kategori dan produk (karena produk menampilkan nama kategori)
        Cache::tags(['categories'])->flush();
        Cache::tags(['products'])->flush();
    }
}