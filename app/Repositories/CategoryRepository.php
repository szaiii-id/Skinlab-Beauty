<?php 

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    // --- 1. EXISTING METHODS (JANGAN DIHAPUS - Dipakai Frontend) ---

    public function getAllCategories(): Collection
    {
        return Category::orderBy('name', 'asc')->get();
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    // --- 2. NEW METHODS (Untuk Admin Management) ---

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function delete(Category $category): bool
    {
        if ($category->products()->exists()) {
            return $category->delete(); 
        }
        return $category->forceDelete();
    }
}