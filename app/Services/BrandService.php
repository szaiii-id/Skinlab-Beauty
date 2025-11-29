<?php

namespace App\Services;

use App\Models\Brand;
use App\Repositories\BrandRepository;
use Illuminate\Support\Facades\Cache;

class BrandService
{
    // Tag khusus untuk grouping cache Brand
    private const CACHE_TAG_BRANDS = 'brands'; 
    private const CACHE_TTL = 86400; // 24 Jam (Data Master jarang berubah)

    protected BrandRepository $brandRepository;

    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    public function getAllBrands()
    {
        // Menggunakan tags()
        return Cache::tags([self::CACHE_TAG_BRANDS])->remember(
            'brands:all',
            self::CACHE_TTL,
            function () {
               return $this->brandRepository->getAllBrands(); 
            }  
        );
    }

    public function findBySlug(string $slug): ?Brand
    {
        return Cache::tags([self::CACHE_TAG_BRANDS])->remember(
            "brand:slug:{$slug}",
            self::CACHE_TTL,
            function () use ($slug) {
                return $this->brandRepository->findBySlug($slug);
            }
        );
    }

    public function findById(int $id): ?Brand
    {
        // Biasanya pencarian by ID untuk internal logic, tidak perlu cache ketat
        // kecuali digunakan di front-end secara masif.
        return $this->brandRepository->findById($id);
    }
}