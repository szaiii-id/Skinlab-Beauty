<?php

use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/catalog', [ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{slug}/{id}', [ProductPageController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{variantId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variantId}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/categories/{slug}', [ProductPageController::class, 'showByCategory'])->name('categories.show');
Route::get('/brands/{slug}', [ProductPageController::class, 'showByBrand'])->name('brands.show');

Route::get('/search', [ProductPageController::class, 'search'])->name('products.search');


Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('Contact'); 
})->name('contact');

Route::get('/faq', function () {
    return Inertia::render('FAQ'); 
})->name('faq');

Route::get('/terms-of-service', function () {
    return Inertia::render('TermsOfService'); 
})->name('terms');

Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy'); 
})->name('privacy');



Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/my-account', function () {
        return Inertia::render('Account/Index'); 
    })->name('account.index');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist', [WishlistController::class, 'delete'])->name('wishlist.delete');

    require __DIR__.'/settings.php';
});