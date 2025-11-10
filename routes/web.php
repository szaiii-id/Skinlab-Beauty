<?php

use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\CartController; 
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ProductPageController::class, 'index'])->name('home');
Route::get('/products', [ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductPageController::class, 'show'])->name('products.show');


Route::post('/cart', [CartController::class, 'store'])->name('cart.store');


Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// require __DIR__.'/settings.php'; 
// require __DIR__.'/auth.php';