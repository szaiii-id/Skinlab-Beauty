<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
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

    public function getNewReleases(): Collection
    {
        return $this->productRepository->getNewReleases();
    }

    public function getBestSellers(): Collection
    {
        return $this->productRepository->getBestSellers();
    }

    public function getProductsByCategoryId(int $categoryId): LengthAwarePaginator
    {
        return $this->productRepository->getProductsByCategoryId($categoryId);
    }

    public function getProductsByBrandId(int $brandId): LengthAwarePaginator
    {
        return $this->productRepository->getProductsByBrandId($brandId);
    }

    public function searchProducts(string $query): LengthAwarePaginator
    {
        return $this->productRepository->searchProducts($query);
    }

    public function getInstantSearchResult(string $query, int $limit = 8): Collection
    {
        return $this->productRepository->getInstantSearchResult($query, $limit);
    }
}