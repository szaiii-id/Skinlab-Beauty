<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator; // <-- WAJIB IMPORT INI
use Illuminate\Database\Eloquent\Collection; // WAJIB IMPORT INI
use Illuminate\Support\Facades\Cache;

class ProductService
{
    private const CACHE_KEY_PRODUCT_DETAIL = 'product:detail:';
    private const CACHE_TTL = 3600;

    protected ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts(): LengthAwarePaginator 
    {

        return $this->productRepository->getAllProductsWithVariants(); 
    }

    public function getProductById(int $id): ?\App\Models\Product
    {
        $cacheKey = self::CACHE_KEY_PRODUCT_DETAIL . $id;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($id) {
                return $this->productRepository->findByIdWithVariants($id);
            }
        );
    }

    // Metode ini mengembalikan Collection (karena Repository menggunakan ->get())
    public function getNewReleases(): Collection
    {
        return $this->productRepository->getNewReleases();
    }

    public function getBestSellers(): Collection
    {
        return $this->productRepository->getBestSellers();
    }

    public function getProductByCategoryById(int $categoryId): LengthAwarePaginator
    {
        return $this->productRepository->getProductsByCategoryById($categoryId);
    }

    public function getProductByBrandById(int $brandId): LengthAwarePaginator
    {
        return $this->productRepository->getProductsByBrandById($brandId);
    }
}