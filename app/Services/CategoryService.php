<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    private const CACHE_KEY_ALL_CATEGORYS = 'categorys:all';
    private const CACHE_TTL = 3600;
    
    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }


    public function getAllCategories() : Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ALL_CATEGORYS, 
            self::CACHE_TTL, 
            function () {
            return $this->categoryRepository->getAllCategories();
        });
    }
}
