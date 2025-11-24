<?php

return [
    'api_key' => env('RAJAONGKIR_API_KEY'),
    'delivery_key' => env('KOMERCE_DELIVERY_KEY'),
    'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
    'origin_id' => env('RAJAONGKIR_ORIGIN_ID'),
    'origin_type' => env('RAJAONGKIR_ORIGIN_TYPE', 'subdistrict'),
    'timeout' => 30,
];
