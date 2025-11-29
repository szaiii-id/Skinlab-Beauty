<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkinProfile;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    private const CACHE_TAG_PRODUCTS = 'products';
    private const CACHE_TTL = 3600; // 1 Jam

    protected ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Generate unique cache key based on query parameters (page, sort, etc.)
     */
    private function getCacheKey(string $prefix): string
    {
        $queryParams = request()->query(); // Ambil semua ?page=1&sort=desc
        ksort($queryParams); // Urutkan parameter agar konsisten
        $queryString = http_build_query($queryParams);
        
        return "{$prefix}:{$queryString}";
    }

    public function getAllProducts(): LengthAwarePaginator 
    {
        // Cache Key contoh: "products:all:page=1"
        $cacheKey = $this->getCacheKey('products:all');

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey, 
            self::CACHE_TTL, 
            fn() => $this->productRepository->getAllProductsWithVariants()
        );
    }

    public function getProductById(int $id): ?\App\Models\Product
    {
        // Cache Key contoh: "product:detail:105"
        $cacheKey = "product:detail:{$id}";

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->productRepository->findByIdWithVariants($id)
        );
    }

    public function getNewReleases(): Collection
    {
        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            'products:new_releases',
            self::CACHE_TTL,
            fn() => $this->productRepository->getNewReleases()
        );
    }

    public function getBestSellers(): Collection
    {
        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            'products:best_sellers',
            self::CACHE_TTL,
            fn() => $this->productRepository->getBestSellers()
        );
    }

    public function getProductsByCategoryId(int $categoryId): LengthAwarePaginator
    {
        $cacheKey = $this->getCacheKey("products:category:{$categoryId}");

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->productRepository->getProductsByCategoryId($categoryId)
        );
    }

    public function getProductsByBrandId(int $brandId): LengthAwarePaginator
    {
        $cacheKey = $this->getCacheKey("products:brand:{$brandId}");

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->productRepository->getProductsByBrandId($brandId)
        );
    }

    /**
     * SEARCH: DO NOT CACHE
     * Alasan: Kombinasi keyword user tidak terbatas. 
     * Jika di-cache, memori Redis akan penuh dengan sampah pencarian.
     */
    public function searchProducts(string $query): LengthAwarePaginator
    {
        return $this->productRepository->searchProducts($query);
    }

    public function getInstantSearchResult(string $query, int $limit = 8): Collection
    {
        // Direct DB untuk akurasi instan
        return $this->productRepository->getInstantSearchResult($query, $limit);
    }

    /**
     * [BARU] Logika Rekomendasi Personal
     * Di-cache per user selama 10 menit agar tidak membebani DB setiap refresh home.
     */
    public function getPersonalizedRecommendations(User $user): array
    {
        $cacheKey = "recommendations:user:{$user->id}";

        // Gunakan tag 'products' agar jika ada produk dihapus, cache ini bisa ikut di-flush (opsional)
        // atau biarkan expire sendiri dalam 10 menit (600 detik).
        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember($cacheKey, 600, function () use ($user) {
            $profile = UserSkinProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return ['products' => [], 'skin_type' => null];
            }

            // Query berat dipindah kesini
            $query = \App\Models\Product::with(['variants', 'category', 'brand'])
                ->whereJsonContains('suitability_tags', $profile->skin_type);

            if (!empty($profile->skin_concerns)) {
                $query->orWhere(function($q) use ($profile) {
                    foreach ($profile->skin_concerns as $concern) {
                        $q->orWhereJsonContains('suitability_tags', $concern);
                    }
                });
            }

            return [
                'products' => $query->inRandomOrder()->take(4)->get(),
                'skin_type' => $profile->skin_type
            ];
        });
    }
}