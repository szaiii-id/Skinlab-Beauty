<?php


namespace App\Services;

use App\Repositories\BrandRepository;
use Illuminate\Support\Facades\Cache;

class BrandService
{
    private const CACHE_KEY_ALL_BRANDS = 'brands:all';
    private const CACHE_TTL = 3600;

    protected BrandRepository $brandRepository;

    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    public function getAllBrands()
    {
        return Cache::remember(
            self::CACHE_KEY_ALL_BRANDS,
            self::CACHE_TTL,
            function () {
               return $this->brandRepository->getAllBrands(); 
            }  
        );
    }
}
