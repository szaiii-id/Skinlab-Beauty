<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{

    private const CACHE_KEY = 'products:all';
    /**
     * Handle the Product "saved" event.
     */
    public function saved(Product $product): void
    {
        Cache::forget(self::CACHE_KEY);
    }
    
    
    
    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
