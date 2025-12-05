<?php 

namespace App\Repositories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection; // Saya perjelas tipe Collection-nya

class BrandRepository
{
    // --- EXISTING METHODS (DO NOT CHANGE) ---
    public function getAllBrands(): Collection
    {
        return Brand::orderBy('name', 'asc')->get();
    }

    public function findBySlug(string $slug): ?Brand
    {
        return Brand::where('slug', $slug)->first();
    }

    public function findById(int $id): ?Brand
    {
        return Brand::find($id);
    }

    // --- NEW ADMIN METHODS (WRITE) ---

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): bool
    {
        return $brand->update($data);
    }

    public function delete(Brand $brand): bool
    {
        if ($brand->products()->exists()) {
            return $brand->delete(); 
        }

        return $brand->forceDelete();
    }
}