<?php

use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OrderReturnController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\SkinAnalysisController;
use App\Http\Controllers\SkincareRoutineController;
use App\Http\Controllers\WishlistController;
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
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{variant}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('/wishlist/{variant}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move-to-cart');
    Route::get('/wishlist/status', [WishlistController::class, 'status'])->name('wishlist.status');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

    // riview product
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');
    // user cancel order
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // order return
    Route::post('/orders/{id}/return', [OrderReturnController::class, 'store'])->name('orders.return');

    // skin analysis
    Route::get('/skin-analysis', [SkinAnalysisController::class, 'index'])->name('skin-analysis.index');
    Route::post('/skin-analysis', [SkinAnalysisController::class, 'store'])->name('skin-analysis.store');

    // skincare routine
    Route::get('/my-routine', [SkincareRoutineController::class, 'index'])->name('routine.index');
    Route::post('/routine', [SkincareRoutineController::class, 'store'])->name('routine.store');
    Route::put('/routine/{id}', [SkincareRoutineController::class, 'update'])->name('routine.update');
    Route::post('/routine/{id}/toggle', [SkincareRoutineController::class, 'toggleCheck'])->name('routine.toggle');
    Route::delete('/routine/{id}', [SkincareRoutineController::class, 'destroy'])->name('routine.destroy');
    Route::delete('/routine/group/{id}', [SkincareRoutineController::class, 'destroyGroup'])->name('routine.destroy-group');
    // rewards
    Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
    Route::post('/rewards/{id}/redeem', [RewardController::class, 'redeem'])->name('rewards.redeem');
    
    // INCLUDE SETTINGS ROUTE DI SINI
    require __DIR__.'/settings.php';
});