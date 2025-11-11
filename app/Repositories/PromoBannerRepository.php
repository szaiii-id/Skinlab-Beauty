<?php


namespace App\Repositories;

use App\Models\PromoBanner;

class PromoBannerRepository
{
    public function getActiveBanners()
    {
        return PromoBanner::where('is_active', true)->orderBy('created_at', 'desc')->get();
    }    
}
