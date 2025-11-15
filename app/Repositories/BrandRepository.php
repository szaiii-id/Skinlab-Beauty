<?php

namespace App\Repositories;

use App\Models\Brand;
use Illuminate\Support\Collection;

class BrandRepository
{
    public function getAllBrands(): Collection
    {
        return Brand::orderBy('name', 'asc')->get();
    }    

    /**
     * Find brand by slug
     *
     * @param string $slug
     * @return Brand|null
     */
    public function findBySlug(string $slug): ?Brand
    {
        return Brand::where('slug', $slug)->first();
    }

    /**
     * Find brand by ID
     *
     * @param int $id
     * @return Brand|null
     */
    public function findById(int $id): ?Brand
    {
        return Brand::find($id);
    }
}