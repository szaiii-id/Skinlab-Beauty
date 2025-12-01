<?php

namespace App\Services;

use App\Models\Brand;
use App\Repositories\BrandRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class BrandService
{
    private const CACHE_TAG_BRANDS = 'brands'; 
    private const CACHE_TTL = 86400; // 24 Hours

    protected BrandRepository $brandRepository;

    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    // --- USER / FRONTEND METHODS (REDIS) ---
    // Kode lama Anda tetap aman di sini

    public function getAllBrands(): Collection
    {
        return Cache::tags([self::CACHE_TAG_BRANDS])->remember(
            'brands:all',
            self::CACHE_TTL,
            fn () => $this->brandRepository->getAllBrands()
        );
    }

    public function findBySlug(string $slug): ?Brand
    {
        return Cache::tags([self::CACHE_TAG_BRANDS])->remember(
            "brand:slug:{$slug}",
            self::CACHE_TTL,
            fn () => $this->brandRepository->findBySlug($slug)
        );
    }

    public function findById(int $id): ?Brand
    {
        return $this->brandRepository->findById($id);
    }

    // --- ADMIN METHODS (ELASTICSEARCH & WRITE) ---

/**
     * Search using Elasticsearch via Scout
     * Updated: Now includes product count
     */
    public function searchForAdmin(string $query = '', int $perPage = 10): LengthAwarePaginator
    {
        if (empty($query)) {
            // Jika tidak mencari, ambil dari DB + Hitung Produk
            return Brand::withCount('products') // <--- TAMBAHAN PENTING
                ->latest()
                ->paginate($perPage);
        }

        // Jika mencari via Elasticsearch
        return Brand::search("*{$query}*")
            ->query(fn ($builder) => $builder->withCount('products')) // <--- TAMBAHAN PENTING (Hydrate dengan count)
            ->paginate($perPage);
    }

    public function createBrand(array $data): Brand
    {
        // Auto Slug logic
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $brand = $this->brandRepository->create($data);

        // Hapus Cache Redis agar data baru muncul di User
        $this->invalidateCache();

        return $brand;
    }

    public function updateBrand(int $id, array $data): bool
    {
        $brand = $this->brandRepository->findById($id);
        if (!$brand) return false;

        // Update slug if name changes (optional)
        if (empty($data['slug']) && isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $updated = $this->brandRepository->update($brand, $data);

        // Hapus Cache Redis
        $this->invalidateCache();

        return $updated;
    }

    public function deleteBrand(int $id): bool
    {
        $brand = $this->brandRepository->findById($id);
        if (!$brand) return false;

        $deleted = $this->brandRepository->delete($brand);

        // Hapus Cache Redis
        $this->invalidateCache();

        return $deleted;
    }

    /**
     * Helper to clear Redis Cache for Brands
     */
    private function invalidateCache(): void
    {
        Cache::tags([self::CACHE_TAG_BRANDS])->flush();
    }
}