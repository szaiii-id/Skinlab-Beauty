<?php


namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function getAllProductsWithVariants(): Collection
    {
        return Product::with('variants', 'category')->orderBy('name', 'asc')->get();

    }

    public function findByIdWithVariants(int $id): ?Product
    {
        return Product::with('variants', 'category')->find($id);
    }
}
