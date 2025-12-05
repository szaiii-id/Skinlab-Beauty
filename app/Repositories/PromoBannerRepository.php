<?php

namespace App\Repositories;

use App\Models\PromoBanner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class PromoBannerRepository
{
    // --- FRONTEND QUERY ---
    public function getActiveBanners()
    {
        $now = Carbon::now();

        return PromoBanner::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $now);
            })
            ->orderBy('start_date', 'asc') 
            ->get();
    }
    
    public function getForDataTable(int $perPage = 10): LengthAwarePaginator
    {
        return PromoBanner::latest()->paginate($perPage);
    }

    public function create(array $data): PromoBanner 
    {
         return PromoBanner::create($data); 
    }
    public function update(PromoBanner $banner, array $data): bool 
    {
         return $banner->update($data); 
    }
    public function delete(PromoBanner $banner): bool 
    {
         return $banner->delete(); 
    }
    
    public function toggleActive(PromoBanner $banner): bool 
    { 
        return $banner->update(['is_active' => !$banner->is_active]); 
    }
}