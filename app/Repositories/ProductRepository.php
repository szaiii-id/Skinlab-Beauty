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
            ->orderBy('name', 'asc') 
            ->paginate(12)
            ->appends(request()->query()); 
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

    public function getProductsByCategoryId(int $categoryId): LengthAwarePaginator
    {
        return Product::with('variants', 'category', 'brand')
            ->where('category_id', $categoryId)
            ->orderBy('name', 'asc') 
            ->paginate(12)
            ->appends(request()->query()); 
    }
        
    public function getProductsByBrandId(int $brandId): LengthAwarePaginator
    {
        return Product::with('variants', 'category', 'brand')
            ->where('brand_id', $brandId)
            ->orderBy('name', 'asc') 
            ->paginate(12)
            ->appends(request()->query()); 
    }

    public function searchProducts(string $query): LengthAwarePaginator
    {
        return Product::with('variants', 'category', 'brand')
            ->where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('description', 'LIKE', '%' . $query . '%')
            ->orWhereHas('brand', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orWhereHas('category', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate(12)
            ->appends(request()->query());
    }

    public function getInstantSearchResult(string $query, int $limit = 8): Collection
    {
        return Product::with('variants', 'category', 'brand')
            ->where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('description', 'LIKE', '%' . $query . '%')
            ->orWhereHas('brand', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orWhereHas('category', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('name', 'asc')
            ->take($limit)
            ->get();
    }
}