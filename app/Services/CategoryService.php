<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    private const CACHE_KEY_ALL_CATEGORIES = 'categories:all';
    private const CACHE_PREFIX_SLUG = 'category:slug:';
    private const CACHE_TTL = 3600;
    
    protected CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getAllCategories(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ALL_CATEGORIES, 
            self::CACHE_TTL, 
            function () {
                return $this->categoryRepository->getAllCategories();
            }
        );
    }

    public function findBySlug(string $slug): ?Category
    {
        $cacheKey = self::CACHE_PREFIX_SLUG . $slug;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($slug) {
                return $this->categoryRepository->findBySlug($slug);
            }
        );
    }

    /**
     * Find category by ID
     *
     * @param int $id
     * @return Category|null
     */
    public function findById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }
}