<?php

namespace App\Services;

use App\Repositories\PromoBannerRepository;
use Illuminate\Support\Facades\Cache;

class PromoBannerService
{
    private const CACHE_KEY = 'promo_banners:active';
    private const CACHE_TTL = 3600; 
    
    protected PromoBannerRepository $promoBannerRepository;

    public function __construct(PromoBannerRepository $promoBannerRepository)
    {
        $this->promoBannerRepository = $promoBannerRepository;
    }

    public function getActiveBanners()
    {
        return Cache::remember(
            self::CACHE_KEY, 
            self::CACHE_TTL, 
            function () {
                return $this->promoBannerRepository->getActiveBanners();
        });
    }
}
