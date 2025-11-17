<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\InstantSearchController;
use App\Http\Controllers\Api\RegionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/instant-search', [InstantSearchController::class, 'index']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth'])->group(function () {
    
    // Regions API - Simple
    Route::get('/regions/provinces', [RegionController::class, 'getProvinces']);
    Route::get('/regions/provinces/{provinceCode}/cities', [RegionController::class, 'getCities']);
    Route::get('/regions/cities/{cityCode}/districts', [RegionController::class, 'getDistricts']);
    
    // Addresses API - Simple  
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{id}/set-default', [AddressController::class, 'setDefault']);
    Route::get('/addresses/default', [AddressController::class, 'getDefault']);
});