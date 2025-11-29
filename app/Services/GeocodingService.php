<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public function search(string $address): array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'q' => $address,
            'limit' => 5,
            'countrycodes' => 'id',
            'addressdetails' => 1
        ]);

        return $response->json() ?? [];
    }

    public function reverse(float $lat, float $lng): array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/reverse', [
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lng,
            'zoom' => 18,
            'addressdetails' => 1
        ]);

        return $response->json() ?? [];
    }
}