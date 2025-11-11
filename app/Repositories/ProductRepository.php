<?php


namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function getAllProductsWithVariants(): Collection
    {
        return Product::with('variants', 'category', 'brand')->orderBy('name', 'asc')->get();

    }

    public function findByIdWithVariants(int $id): ?Product
    {
        return Product::with('variants', 'category', 'brand')
        ->select('id', 'slug', 'category_id', 'brand_id', 'name', 'description')
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
}
