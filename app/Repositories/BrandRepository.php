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
}
