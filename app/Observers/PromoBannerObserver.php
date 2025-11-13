<?php

namespace App\Observers;

use App\Models\PromoBanner;
use Illuminate\Support\Facades\Cache;
class PromoBannerObserver
{
    private const CACHE_KEY = 'promo_banners:active';

    /**
     * Handle the PromoBanner "saved" event (Dipicu saat created atau updated).
     */
    public function saved(PromoBanner $promoBanner): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Handle the PromoBanner "deleted" event.
     */
    public function deleted(PromoBanner $promoBanner): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
