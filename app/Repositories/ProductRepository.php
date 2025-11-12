<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function getAllProductsWithVariants(): LengthAwarePaginator
    {
        return Product::with('variants', 'category', 'brand')
            ->orderBy('id', 'asc') // ✅ Ganti ke ID untuk consistency
            ->paginate(12);
    }

    public function findByIdWithVariants(int $id): ?Product
    {
        return Product::with('variants', 'category', 'brand')
            ->find($id);
    }

    public function getNewReleases(int $limit = 4): Collection
    {
        return Product::with('variants', 'category', 'brand')
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getBestSellers(int $limit = 4): Collection
    {
        return Product::with('variants', 'category', 'brand')
            ->oldest()
            ->take($limit)
            ->get();
    }

    public function getProductsByCategoryById(int $categoryId): LengthAwarePaginator
    {
        return Product::with('variants', 'category', 'brand')
            ->where('category_id', $categoryId)
            ->orderBy('id', 'asc') // ✅ Tambah orderBy
            ->paginate(12);
    }
        
    public function getProductsByBrandById(int $brandId): LengthAwarePaginator
    {
        return Product::with('variants', 'category', 'brand')
            ->where('brand_id', $brandId)
            ->orderBy('id', 'asc') // ✅ Tambah orderBy
            ->paginate(12);
    }
}