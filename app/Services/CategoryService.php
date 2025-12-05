<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CategoryService
{
    // Config Cache Redis
    private const CACHE_TAG = 'categories';
    private const CACHE_KEY_ALL = 'categories:all';
    private const CACHE_TTL = 86400; // 24 Jam

    protected CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    // --- FRONTEND LOGIC (REDIS) ---
    // Method ini dipanggil oleh User (Catalog)
    public function getAllCategories(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            self::CACHE_KEY_ALL,
            self::CACHE_TTL,
            fn () => $this->repository->getAllCategories()
        );
    }

    // --- ADMIN LOGIC (ELASTICSEARCH & WRITE) ---

    // 1. Search canggih untuk Tabel Admin
    public function searchForAdmin(string $query = '', int $perPage = 10): LengthAwarePaginator
    {
        if (empty($query)) {
            // Jika tidak cari, ambil dari DB biasa (terbaru)
            return Category::latest()->paginate($perPage);
        }

        // Jika cari, pakai ELASTICSEARCH (Scout)
        return Category::search("*{$query}*")->paginate($perPage);
    }

    // 2. Create
    public function createCategory(array $data): Category
    {
        // Auto Slug
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category = $this->repository->create($data);

        // RESET CACHE REDIS (Agar user lihat data baru)
        $this->invalidateCache();

        return $category;
    }

    // 3. Update
    public function updateCategory(int $id, array $data): bool
    {
        $category = $this->repository->findById($id);
        if (!$category) return false;
        
        if (empty($data['slug']) && isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $updated = $this->repository->update($category, $data);

        // RESET CACHE REDIS
        $this->invalidateCache();

        return $updated;
    }

    // 4. Delete
    public function deleteCategory(int $id): bool
    {
        $category = $this->repository->findById($id);
        if (!$category) return false;

        $deleted = $this->repository->delete($category);

        // RESET CACHE REDIS
        $this->invalidateCache();

        return $deleted;
    }

    // Helper Hapus Cache
    private function invalidateCache(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
    
    // Proxy method untuk frontend (jika masih dipakai di tempat lain)
    public function findBySlug(string $slug): ?Category
    {
        return $this->repository->findBySlug($slug);
    }
}