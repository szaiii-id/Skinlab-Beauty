<?php
// [file name]: app/Http\Controllers\Api\AddressController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
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
            Log::error('Error retrieving addresses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses'
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Store address request:', $request->all());

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
                Log::error('Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['user_id'] = $request->user()->id;
            
            $data['is_default'] = $data['is_default'] ?? false;
            $data['is_active'] = true;

            Log::info('Creating address with data:', $data);

            DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    UserAddress::where('user_id', $data['user_id'])
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                }

                UserAddress::create($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'Address created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating address: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create address: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            Log::info('Update address request:', ['id' => $id, 'data' => $request->all()]);

            // VALIDASI DIPERBARUI DENGAN LATITUDE & LONGITUDE
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
                'is_default' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                Log::error('Update validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $userId = $request->user()->id;
            
            $address = UserAddress::where('user_id', $userId)
                                ->where('id', $id)
                                ->first();

            if (!$address) {
                Log::warning('Address not found:', ['user_id' => $userId, 'address_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found'
                ], 404);
            }

            if (isset($data['is_default'])) {
                $data['is_default'] = (bool)$data['is_default'];
            }

            Log::info('Updating address with data:', $data);

            DB::transaction(function () use ($address, $data, $userId) {
                if (isset($data['is_default']) && $data['is_default']) {
                    UserAddress::where('user_id', $userId)
                        ->where('id', '!=', $address->id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                }

                $address->update($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating address: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address: ' . $e->getMessage()
            ], 500);
        }
    }

    public function geocode(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'address' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $address = $request->input('address');
            
            $response = file_get_contents(
                'https://nominatim.openstreetmap.org/search?format=json&q=' . 
                urlencode($address) . '&limit=5&countrycodes=id'
            );
            
            $results = json_decode($response, true);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Geocoding results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Geocoding error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to geocode address'
            ], 500);
        }
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lat = $request->input('lat');
            $lng = $request->input('lng');
            
            $response = file_get_contents(
                "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1"
            );
            
            $result = json_decode($response, true);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Reverse geocoding result retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Reverse geocoding error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reverse geocode coordinates'
            ], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $address = UserAddress::where('user_id', $userId)->where('id', $id)->first();

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found'
                ], 404);
            }

            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setDefault(Request $request, $id): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            
            $address = UserAddress::where('user_id', $userId)->where('id', $id)->first();
            
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found'
                ], 404);
            }
            
            DB::transaction(function () use ($userId, $id) {
                UserAddress::where('user_id', $userId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                UserAddress::where('user_id', $userId)
                    ->where('id', $id)
                    ->update(['is_default' => true]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Default address set successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error setting default address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default address: ' . $e->getMessage()
            ], 500);
        }
    }

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
            Log::error('Error retrieving default address: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve default address'
            ], 500);
        }
    }
}