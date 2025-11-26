<?php

use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WhislistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- PUBLIC ROUTES ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalog', [ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{slug}/{id}', [ProductPageController::class, 'show'])->name('products.show');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{variantId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variantId}', [CartController::class, 'destroy'])->name('cart.destroy');

// Categories & Search
Route::get('/categories/{slug}', [ProductPageController::class, 'showByCategory'])->name('categories.show');
Route::get('/brands/{slug}', [ProductPageController::class, 'showByBrand'])->name('brands.show');
Route::get('/search', [ProductPageController::class, 'search'])->name('products.search');

// Static Pages
Route::get('/about', fn() => Inertia::render('About'))->name('about');
Route::get('/contact', fn() => Inertia::render('Contact'))->name('contact');
Route::get('/faq', fn() => Inertia::render('FAQ'))->name('faq');
Route::get('/terms-of-service', fn() => Inertia::render('TermsOfService'))->name('terms');
Route::get('/privacy-policy', fn() => Inertia::render('PrivacyPolicy'))->name('privacy');

// --- AUTH & VERIFICATION ---
Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
Route::post('/email/verify', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/verification-notification', [VerificationController::class, 'send'])->name('verification.send');

// --- PROTECTED ROUTES (DASHBOARD & SETTINGS) ---
Route::middleware(['auth', 'verified'])->group(function () {

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    
    // Dashboard (Gunakan Controller, hapus duplikasi closure function)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Account
    Route::get('/my-account', fn() => Inertia::render('Account/Index'))->name('account.index');
    
    // Wishlist & Orders
    Route::get('/wishlist', [WhislistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WhislistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{variant}', [WhislistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('/wishlist/{variant}/move-to-cart', [WhislistController::class, 'moveToCart'])->name('wishlist.move-to-cart');
    Route::get('/wishlist/status', [WhislistController::class, 'status'])->name('wishlist.status');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    // INCLUDE SETTINGS ROUTE DI SINI
    require __DIR__.'/settings.php';
});