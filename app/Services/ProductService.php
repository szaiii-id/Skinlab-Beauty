<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    private const CACHE_KEY_ALL_PRODUCTS = 'products:all';
    private const CACHE_KEY_PRODUCT_DETAIL = 'product:detail:';
    private const CACHE_TTL = 3600;

    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts()
    {
        return Cache::remember(
            self::CACHE_KEY_ALL_PRODUCTS,
            self::CACHE_TTL,
            function () {
                return $this->productRepository->getAllProductsWithVariants(); 
            }
        );
    }

    public function getProductById(int $id)
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
}