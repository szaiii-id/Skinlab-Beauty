<?php

namespace App\Services;

use App\Models\Brand;
use App\Repositories\BrandRepository;
use Illuminate\Support\Facades\Cache;

class BrandService
{
    private const CACHE_KEY_ALL_BRANDS = 'brands:all';
    private const CACHE_PREFIX_SLUG = 'brand:slug:';
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

    public function findBySlug(string $slug): ?Brand
    {
        $cacheKey = self::CACHE_PREFIX_SLUG . $slug;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($slug) {
                return $this->brandRepository->findBySlug($slug);
            }
        );
    }

    /**
     * Find brand by ID
     *
     * @param int $id
     * @return Brand|null
     */
    public function findById(int $id): ?Brand
    {
        return $this->brandRepository->findById($id);
    }
}