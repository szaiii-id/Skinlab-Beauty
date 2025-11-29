<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    private const CACHE_TAG_CATEGORIES = 'categories';
    private const CACHE_TTL = 86400; // 24 Jam
    
    protected CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getAllCategories(): Collection
    {
        return Cache::tags([self::CACHE_TAG_CATEGORIES])->remember(
            'categories:all', 
            self::CACHE_TTL, 
            function () {
                return $this->categoryRepository->getAllCategories();
            }
        );
    }

    public function findBySlug(string $slug): ?Category
    {
        return Cache::tags([self::CACHE_TAG_CATEGORIES])->remember(
            "category:slug:{$slug}",
            self::CACHE_TTL,
            function () use ($slug) {
                return $this->categoryRepository->findBySlug($slug);
            }
        );
    }

    public function findById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }
}