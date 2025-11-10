<?php

// use App\Http\Controllers\Api\CategoryController;
// use App\Http\Controllers\Api\ProductController;
// use App\Http\Controllers\ProductPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::apiResource('categories', CategoryController::class)->only(['index']);
// Route::apiResource('products', ProductController::class)->only(['index', 'show']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});