<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    protected RegionService $regionService;

    public function __construct(RegionService $regionService)
    {
        $this->regionService = $regionService;
    }

    public function getProvinces(): JsonResponse
    {
        try {
            // Ambil dari Redis via Service
            $provinces = $this->regionService->getProvinces();
            
            return response()->json([
                'success' => true,
                'data' => $provinces,
                'message' => 'Provinces retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function getCities($provinceCode): JsonResponse
    {
        if (!$provinceCode) {
            return response()->json(['success' => false, 'message' => 'Province code required'], 400);
        }

        try {
            $cities = $this->regionService->getCities($provinceCode);
            
            return response()->json([
                'success' => true,
                'data' => $cities,
                'message' => 'Cities retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function getDistricts($cityCode): JsonResponse
    {
        if (!$cityCode) {
            return response()->json(['success' => false, 'message' => 'City code required'], 400);
        }

        try {
            $districts = $this->regionService->getDistricts($cityCode);
            
            return response()->json([
                'success' => true,
                'data' => $districts,
                'message' => 'Districts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }
}