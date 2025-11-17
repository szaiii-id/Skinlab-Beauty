<?php
// [file name]: app/Http/Controllers/Api/RegionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;

class RegionController extends Controller
{
    public function getProvinces(): JsonResponse
    {
        try {
            $provinces = Province::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $provinces,
                'message' => 'Provinces retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve provinces'
            ], 500);
        }
    }

    public function getCities($provinceCode): JsonResponse
    {
        try {
            $cities = City::where('province_code', $provinceCode)
                         ->orderBy('name')
                         ->get();
            
            return response()->json([
                'success' => true,
                'data' => $cities,
                'message' => 'Cities retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cities'
            ], 500);
        }
    }

    public function getDistricts($cityCode): JsonResponse
    {
        try {
            $districts = District::where('city_code', $cityCode)
                               ->orderBy('name')
                               ->get();
            
            return response()->json([
                'success' => true,
                'data' => $districts,
                'message' => 'Districts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve districts'
            ], 500);
        }
    }
}