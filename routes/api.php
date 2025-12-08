<?php

use App\Http\Controllers\Api\InstantSearchController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Route di sini tidak memiliki session. Gunakan web.php untuk route
| yang membutuhkan login user (FCM, Address, dll).
*/

// Public Routes (No Auth Required)
Route::get('/instant-search', [InstantSearchController::class, 'index']);
Route::post('/shipping/check-rates', [ShippingController::class, 'checkRates']);

// Callbacks / Webhooks (Dari Pihak Ketiga)
Route::post('/midtrans-callback', [PaymentCallbackController::class, 'handle']);
Route::post('/webhook/komerce', [ShippingController::class, 'handleWebhook']);

// Tracking Public (Opsional, jika ada fitur lacak tanpa login)
Route::get('/orders/{id}/track-public', [ShippingController::class, 'trackShipment']);