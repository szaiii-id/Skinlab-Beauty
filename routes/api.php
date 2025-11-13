<?php

use App\Http\Controllers\Api\InstantSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/instant-search', [InstantSearchController::class, 'index']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});