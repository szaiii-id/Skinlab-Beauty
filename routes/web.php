<?php

use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/catalog', [ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{slug}/{id}', [ProductPageController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');



Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/my-account', function () {
        return Inertia::render('Account/Index'); 
    })->name('account.index');

    require __DIR__.'/settings.php';
});