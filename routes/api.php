<?php
// [file name]: api.php

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
    
    // Regions API - PERBAIKI ENDPOINT INI
    Route::get('/regions/provinces', [RegionController::class, 'getProvinces']);
    
    // PERBAIKAN: Ubah endpoint cities sesuai dengan frontend
    Route::get('/regions/cities/{provinceCode}', [RegionController::class, 'getCities']);
    
    // PERBAIKAN: Ubah endpoint districts sesuai dengan frontend  
    Route::get('/regions/districts/{cityCode}', [RegionController::class, 'getDistricts']);
    
    // Addresses API - Tetap sama
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{id}/set-default', [AddressController::class, 'setDefault']);
    Route::get('/addresses/default', [AddressController::class, 'getDefault']);
});