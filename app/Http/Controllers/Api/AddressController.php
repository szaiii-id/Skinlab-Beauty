<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use App\Services\AddressService;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    protected AddressService $addressService;
    protected GeocodingService $geocodingService;

    public function __construct(AddressService $addressService, GeocodingService $geocodingService)
    {
        $this->addressService = $addressService;
        $this->geocodingService = $geocodingService;
    }

    /**
     * Get all addresses for current user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $addresses = UserAddress::with(['province', 'city', 'district'])
                ->where('user_id', $request->user()->id)
                ->active()
                ->orderBy('is_default', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $addresses,
                'message' => 'Addresses retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Get Addresses Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to retrieve addresses'], 500);
        }
    }

    /**
     * Create new address
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_name' => 'required|string|max:100',
            'phone_number' => 'required|string|max:20',
            'province_code' => 'required|string',
            'city_code' => 'required|string',
            'district_code' => 'required|string',
            'full_address' => 'required|string|max:500',
            'postal_code' => 'required|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'type' => 'required|in:home,office,other',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $this->addressService->createAddress($request->user()->id, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Address created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create Address Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create address'], 500);
        }
    }

    /**
     * Update existing address
     */
    public function update(Request $request, $id): JsonResponse
    {
        $userId = $request->user()->id;
        $address = UserAddress::where('user_id', $userId)->find($id);

        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Address not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'receiver_name' => 'sometimes|string|max:100',
            'phone_number' => 'sometimes|string|max:20',
            'province_code' => 'sometimes|string',
            'city_code' => 'sometimes|string',
            'district_code' => 'sometimes|string',
            'full_address' => 'sometimes|string|max:500',
            'postal_code' => 'sometimes|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'type' => 'sometimes|in:home,office,other',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $this->addressService->updateAddress($address, $validator->validated());

            return response()->json(['success' => true, 'message' => 'Address updated successfully']);
        } catch (\Exception $e) {
            Log::error('Update Address Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update address'], 500);
        }
    }

    /**
     * Delete address
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $deleted = $this->addressService->deleteAddress($request->user()->id, $id);

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Address not found'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Address deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Delete Address Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete address'], 500);
        }
    }

    /**
     * Set address as default
     */
    public function setDefault(Request $request, $id): JsonResponse
    {
        try {
            $success = $this->addressService->setAsDefault($request->user()->id, $id);
            
            if (!$success) {
                return response()->json(['success' => false, 'message' => 'Address not found'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Default address set successfully']);
        } catch (\Exception $e) {
            Log::error('Set Default Address Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to set default address'], 500);
        }
    }

    /**
     * Get default address
     */
    public function getDefault(Request $request): JsonResponse
    {
        try {
            $defaultAddress = UserAddress::with(['province', 'city', 'district'])
                ->where('user_id', $request->user()->id)
                ->default()
                ->active()
                ->first();

            return response()->json([
                'success' => true,
                'data' => $defaultAddress,
                'message' => 'Default address retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Get Default Address Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to retrieve default address'], 500);
        }
    }

    /**
     * Geocode address to coordinates
     */
    public function geocode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $results = $this->geocodingService->search($request->address);
            return response()->json([
                'success' => true, 
                'data' => $results, 
                'message' => 'Geocoding results retrieved'
            ]);
        } catch (\Exception $e) {
            Log::error('Geocode Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Geocoding failed'], 500);
        }
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->geocodingService->reverse($request->lat, $request->lng);
            return response()->json([
                'success' => true, 
                'data' => $result, 
                'message' => 'Reverse geocoding result retrieved'
            ]);
        } catch (\Exception $e) {
            Log::error('Reverse Geocode Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Reverse Geocoding failed'], 500);
        }
    }
}