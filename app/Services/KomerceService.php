<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KomerceService
{
    private const BASE_URL = 'https://rajaongkir.komerce.id/api/v1/destination/domestic-destination';
    private const CACHE_TTL = 604800; // Cache 7 Hari

    public function findDestinationId(string $districtName, string $cityName): ?int
    {
        $cleanCity = trim(str_replace(['KOTA ', 'KABUPATEN '], '', strtoupper($cityName)));
        $cleanDistrict = trim(strtoupper($districtName));
        
        // Cache Key Unik
        $cacheKey = "komerce:id:{$cleanDistrict}:{$cleanCity}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cleanDistrict, $cleanCity) {
            return $this->fetchFromApi($cleanDistrict, $cleanCity);
        });
    }

    private function fetchFromApi(string $district, string $city): ?int
    {
        try {
            $apiKey = config('rajaongkir.api_key');
            $query = "$district $city";
            
            Log::info("🔍 Komerce API Hit: " . $query);

            $response = Http::withHeaders(['key' => $apiKey])
                ->timeout(5)
                ->get(self::BASE_URL, ['search' => $query]);

            if ($response->successful()) {
                $results = $response->json()['data'] ?? [];
                if (!empty($results)) {
                    return (int) $results[0]['id'];
                }
            }
        } catch (\Exception $e) {
            Log::error("Komerce API Error: " . $e->getMessage());
        }
        return null;
    }
}