<?php

namespace App\Http\Middleware;

use App\Services\CategoryService;
use App\Services\BrandService;
use App\Services\PromoBannerService;
use App\Services\WhislistService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
// IMPORT MODEL LENGKAP
use App\Models\Order;
use App\Models\UserReward;
use App\Models\SkincareRoutine;
use App\Models\UserSkinProfile; // <--- TAMBAHAN BARU

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        // 1. LOGIKA CART & WISHLIST
        $cartSession = $request->session()->get('cart', []);
        $cartCount = count($cartSession);
        $wishlistSession = $request->session()->get('wishlist', []);
        $wishlistCount = count($wishlistSession);

        // 2. LOGIKA STATUS & BADGES LAIN
        $activeOrderStatus = null;
        $hasRewards = false;
        $hasRoutine = false;
        $hasSkinAnalysis = false; // Default false

        if ($user && $user instanceof \App\Models\User) {
            // Order Status
            $activeOrderStatus = Order::where('user_id', $user->id)
                ->whereIn('order_status', ['pending', 'processing', 'pickup_scheduled', 'shipped'])
                ->orderByRaw("FIELD(order_status, 'shipped', 'pickup_scheduled', 'processing', 'pending')")
                ->value('order_status');

            // Rewards
            $hasRewards = UserReward::where('user_id', $user->id)
                ->where('is_used', false)
                ->exists();
            
            // Routine (Badge Setup)
            $hasRoutine = SkincareRoutine::where('user_id', $user->id)->exists();

            // Skin Analysis (Badge Start) - LOGIKA BARU
            $hasSkinAnalysis = UserSkinProfile::where('user_id', $user->id)->exists();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            
            // --- GLOBAL DATA ---
            'categories' => fn () => resolve(CategoryService::class)->getAllCategories(),
            'brands' => fn () => resolve(BrandService::class)->getAllBrands(),
            'promoBanners' => fn () => resolve(PromoBannerService::class)->getActiveBanners(),
            
            // --- COUNTER HEADER ---
            'cartCount' => $cartCount,
            'wishlistCount' => $wishlistCount,
            
            'pendingOrdersCount' => function () use ($request) {
                if ($request->user() && $request->user() instanceof \App\Models\User) {
                    return \App\Models\Order::where('user_id', $request->user()->id)
                        ->whereIn('order_status', ['pending', 'paid', 'shipped'])
                        ->count();
                }
                return 0;
            },

            // --- DATA SIDEBAR BADGES (LENGKAP SEMUA FITUR) ---
            'sidebar_badges' => [
                'cart_count'        => $cartCount,
                'wishlist_count'    => $wishlistCount,
                'order_status'      => $activeOrderStatus,
                'has_rewards'       => $hasRewards,
                'has_routine'       => $hasRoutine,      // Setup Badge
                'has_skin_analysis' => $hasSkinAnalysis, // Start Badge (Baru)
            ],

            'counts' => function () use ($user) {
                if (!$user) return [];

                return [
                    'open_orders' => Order::whereIn('order_status', [
                        'pending',          // COD / Menunggu Konfirmasi
                        'processing',       // Sudah Bayar / Packing
                        'schedule_pickup',  // Siap Request Pickup
                        'pickup_scheduled', // Menunggu Kurir
                        'PICKUP_SCHEDULED',  // Variasi huruf besar
                        'cancellation_requested'
                    ])->count(),

                    'pending_returns' => \App\Models\OrderReturn::where('status', 'pending')->count(),
                    'pending_bans' => \App\Models\BanRequest::where('status', 'pending')->count(),
                    'pending_reviews' => \App\Models\Review::whereNull('admin_reply')->count(),
                ];
            },

            // --- AUTH ---
            'auth' => [
                'user' => $user ? (
                    $user instanceof \App\Models\User 
                        ? $user->load(['membership', 'skinProfile']) 
                        : $user 
                ) : null,
            ],

            // --- FLASH ---
            'flash' => [
                'success' => fn () => $request->session()->get('toast_success'),
                'error' => fn () => $request->session()->get('toast_error') ?? $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'snap_token' => fn () => $request->session()->get('snap_token'),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}