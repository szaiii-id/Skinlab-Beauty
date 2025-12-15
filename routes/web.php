<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

// Controllers User
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReturnController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\SkinAnalysisController;
use App\Http\Controllers\SkincareRoutineController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\NotificationController;

// Controllers API (Internal)
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\FcmController;
use App\Http\Controllers\Api\RegionController;

// Controllers Admin
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\RewardController as AdminRewardController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\BanRequestController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminReturnController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminSkinAnalysisController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StockOpnameController;

/*
|--------------------------------------------------------------------------
| WEB ROUTES (Frontend & Backend & API Internal)
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. PUBLIC ROUTES
// =========================================================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', fn() => Inertia::render('About'))->name('about');
Route::get('/contact', fn() => Inertia::render('Contact'))->name('contact');
Route::get('/faq', fn() => Inertia::render('FAQ'))->name('faq');
Route::get('/terms-of-service', fn() => Inertia::render('TermsOfService'))->name('terms');
Route::get('/privacy-policy', fn() => Inertia::render('PrivacyPolicy'))->name('privacy');

// Auth Verification
Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
Route::post('/email/verify', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/verification-notification', [VerificationController::class, 'send'])->name('verification.send');

// =========================================================================
// 2. PROTECTED USER ROUTES (Must Login)
// =========================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // --- API INTERNAL ---
    Route::prefix('api')->group(function() {
        Route::get('/user', function (Request $request) { return $request->user(); });
        Route::post('/fcm-token', [FcmController::class, 'store']);

        // Address Management
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        Route::patch('/addresses/{id}/set-default', [AddressController::class, 'setDefault']);
        Route::get('/addresses/default', [AddressController::class, 'getDefault']);
        Route::post('/addresses/geocode', [AddressController::class, 'geocode']);
        Route::post('/addresses/reverse-geocode', [AddressController::class, 'reverseGeocode']);

        // Region Data
        Route::get('/regions/provinces', [RegionController::class, 'getProvinces']);
        Route::get('/regions/cities/{provinceCode}', [RegionController::class, 'getCities']);
        Route::get('/regions/districts/{cityCode}', [RegionController::class, 'getDistricts']);

        Route::get('/orders/{id}/track', [OrderController::class, 'track'])->name('orders.track-api');
    });

    // --- SHOPPING FEATURES ---
    Route::get('/catalog', [ProductPageController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}/{id}', [ProductPageController::class, 'show'])->name('products.show');
    Route::get('/categories/{slug}', [ProductPageController::class, 'showByCategory'])->name('categories.show');
    Route::get('/brands/{slug}', [ProductPageController::class, 'showByBrand'])->name('brands.show');
    Route::get('/search', [ProductPageController::class, 'search'])->name('products.search');
    Route::get('/promo/{id}', [ProductPageController::class, 'promo'])->name('products.promo');

    // Cart & Checkout
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{variantId}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{variantId}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    
    // Dashboard & Profile
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{variant}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('/wishlist/{variant}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move-to-cart');
    Route::get('/wishlist/status', [WishlistController::class, 'status'])->name('wishlist.status');
    
    // Orders & Tracking (User Side)
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{id}/return', [OrderReturnController::class, 'store'])->name('orders.return');
    Route::get('/orders/{id}/track', [OrderController::class, 'track'])->name('orders.track-user');
    Route::post('/orders/{id}/complete', [OrderController::class, 'complete'])->name('orders.complete');

    // Reviews & Rewards
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
    Route::post('/rewards/{id}/redeem', [RewardController::class, 'redeem'])->name('rewards.redeem');

    // Skin Analysis & Routine
    Route::get('/skin-analysis', [SkinAnalysisController::class, 'index'])->name('skin-analysis.index');
    Route::post('/skin-analysis', [SkinAnalysisController::class, 'store'])->name('skin-analysis.store');
    Route::get('/my-routine', [SkincareRoutineController::class, 'index'])->name('routine.index');
    Route::post('/routine', [SkincareRoutineController::class, 'store'])->name('routine.store');
    Route::put('/routine/{id}', [SkincareRoutineController::class, 'update'])->name('routine.update');
    Route::post('/routine/{id}/toggle', [SkincareRoutineController::class, 'toggleCheck'])->name('routine.toggle');
    Route::delete('/routine/{id}', [SkincareRoutineController::class, 'destroy'])->name('routine.destroy');
    Route::delete('/routine/group/{id}', [SkincareRoutineController::class, 'destroyGroup'])->name('routine.destroy-group');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::delete('/notifications/clear', [NotificationController::class, 'destroy'])->name('notifications.clear');

    require __DIR__.'/settings.php';
});

// =========================================================================
// 3. ADMIN ROUTES (Skin Lab Center)
// =========================================================================
Route::prefix('skinlab-center')->name('admin.')->group(function() {

    // --- GUEST ADMIN ---
    Route::middleware('guest:admin')->group(function() {
        Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    });

    // --- AUTHENTICATED ADMIN ---
    Route::middleware('auth:admin')->group(function() {
        
        // 3.1 COMMON ROUTES (Access for ALL Roles)
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');


        // 3.2 SUPER ADMIN ONLY AREA
        // Only the 'God Mode' user can manage other staff
        Route::middleware('role:super_admin')->group(function() {
            Route::resource('staff', AdminController::class)->except(['show']);
            Route::patch('staff/{id}/toggle', [AdminController::class, 'toggleStatus'])->name('staff.toggle');
        });


        // 3.3 WAREHOUSE AREA (Super Admin + Warehouse)
        // Access to Products, Stock, Orders, Returns
        Route::middleware('role:super_admin,warehouse')->group(function() {
            
            // Master Data (Product & Stock)
            Route::resource('products', ProductController::class);
            Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);
            Route::resource('brands', BrandController::class)->except(['create', 'show', 'edit']);
            
            // Stock Opname
            Route::controller(StockOpnameController::class)
                ->prefix('stock-opname')
                ->name('stock-opname.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{id}/edit', 'edit')->name('edit');
                    Route::put('/{id}', 'update')->name('update');
                    Route::post('/{id}/finish', 'finish')->name('finish');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });

            // Order Management (Fulfillment)
            Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
            
            // Order Actions
            Route::post('orders/{id}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
            Route::post('orders/{id}/book', [AdminOrderController::class, 'book'])->name('orders.book');
            Route::get('orders/{id}/label', [AdminOrderController::class, 'printLabel'])->name('orders.label');
            Route::post('orders/schedule-pickup', [AdminOrderController::class, 'schedulePickup'])->name('orders.schedule-pickup');
            
            // Bulk Actions
            Route::post('/orders/bulk-book', [AdminOrderController::class, 'bulkBook'])->name('orders.bulk-book');
            Route::post('/orders/bulk-schedule-pickup', [AdminOrderController::class, 'bulkSchedulePickup'])->name('orders.bulk-schedule-pickup');
            Route::post('/orders/bulk-schedule', [AdminOrderController::class, 'schedulePickup'])->name('orders.schedule-pickup');
            Route::post('/orders/bulk-cancel', [AdminOrderController::class, 'bulkCancel'])->name('orders.bulk-cancel');
            Route::post('/orders/bulk-update-status', [AdminOrderController::class, 'bulkUpdateStatus'])->name('orders.bulk-update-status');
            Route::post('/orders/bulk-print-labels', [AdminOrderController::class, 'bulkPrintLabels'])->name('orders.bulk-print-labels');
            Route::post('/orders/{id}/reject-cancellation', [AdminOrderController::class, 'rejectCancellation'])->name('orders.reject-cancellation');

            // Return Management
            Route::resource('returns', AdminReturnController::class)->only(['index', 'show', 'update']);
        });


        // 3.4 MARKETING AREA (Super Admin + Marketing)
        // Access to Banners, Reviews, Skin Analysis, Customers, Rewards
        Route::middleware('role:super_admin,marketing')->group(function() {
            
            // Banners
            Route::resource('banners', BannerController::class)->except(['create', 'edit', 'show']);
            Route::post('banners/{id}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');
            
            // Rewards
            Route::resource('rewards', AdminRewardController::class)->except(['show']);

            // Reviews
            Route::prefix('reviews')->name('reviews.')->controller(AdminReviewController::class)->group(function() {
                Route::get('/', 'index')->name('index');
                Route::patch('/{id}/toggle', 'toggleHidden')->name('toggle');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/reply', 'reply')->name('reply');
            });

            // Skin Analysis
            Route::prefix('skin-analysis')->name('skin-analysis.')->controller(AdminSkinAnalysisController::class)->group(function() {
                Route::get('/', 'index')->name('index');
                Route::get('/{id}', 'show')->name('show');
                Route::post('/{id}/recommend', 'sendRecommendation')->name('recommend');
                Route::post('/bulk-recommend', 'bulkRecommend')->name('bulk-recommend');
                // In routes/web.php inside the admin/skin-analysis group:
            });

            // Customer & Ban Management
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::post('customers/send-gift', [CustomerController::class, 'sendGift'])->name('customers.send-gift');
            Route::post('customers/request-ban', [CustomerController::class, 'requestBan'])->name('customers.request-ban');
            Route::post('customers/unban', [CustomerController::class, 'unban'])->name('customers.unban');
            
            // Ban Approval
            Route::prefix('ban-requests')->name('ban-requests.')->group(function() {
                Route::get('/', [BanRequestController::class, 'index'])->name('index');
                Route::post('/{id}/approve', [BanRequestController::class, 'approve'])->name('approve');
                Route::post('/{id}/reject', [BanRequestController::class, 'reject'])->name('reject');
            });
        });


        // 3.5 REPORTING AREA (Shared Access)
        // Usually, Super Admin, Marketing, and Warehouse all need reports
        Route::middleware('role:super_admin,marketing,warehouse')->group(function() {
            Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function() {
                Route::get('/', 'index')->name('index');
                Route::get('/print', 'print')->name('print');
                Route::get('/export', 'export')->name('export');
            });
        });

    });
});