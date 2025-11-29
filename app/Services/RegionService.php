<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Illuminate\Database\Eloquent\Collection;

class RegionService
{
    // Cache selama 30 Hari (Karena data wilayah sangat jarang berubah)
    private const CACHE_TTL = 2592000; 
    private const CACHE_TAG = 'regions';

    public function getProvinces(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            'regions:provinces', 
            self::CACHE_TTL, 
            function () {
                return Province::orderBy('name', 'asc')->get();
            }
        );
    }

    public function getCities(string $provinceCode): Collection
    {
        $cacheKey = "regions:cities:{$provinceCode}";

        return Cache::tags([self::CACHE_TAG])->remember(
            $cacheKey, 
            self::CACHE_TTL, 
            function () use ($provinceCode) {
                return City::where('province_code', $provinceCode)
                    ->orderBy('name', 'asc')
                    ->get();
            }
        );
    }

    public function getDistricts(string $cityCode): Collection
    {
        $cacheKey = "regions:districts:{$cityCode}";

        return Cache::tags([self::CACHE_TAG])->remember(
            $cacheKey, 
            self::CACHE_TTL, 
            function () use ($cityCode) {
                return District::where('city_code', $cityCode)
                    ->orderBy('name', 'asc')
                    ->get();
            }
        );
    }
}