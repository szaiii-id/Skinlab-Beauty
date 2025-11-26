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

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            
            // --- DATA E-COMMERCE GLOBAL ANDA ---
            'categories' => fn () => resolve(CategoryService::class)->getAllCategories(),
            'brands' => fn () => resolve(BrandService::class)->getAllBrands(),
            'cartCount' => fn () => count($request->session()->get('cart', [])),
            'wishlistCount' => fn () => count($request->session()->get('wishlist', [])), // HANYA SATU INI
            'promoBanners' => fn () => resolve(PromoBannerService::class)->getActiveBanners(),
            'pendingOrdersCount' => $request->user() 
            ? \App\Models\Order::where('user_id', $request->user()->id)
                ->whereIn('order_status', ['pending', 'paid', 'shipped']) // Status yang dianggap "Belum Selesai"
                ->count() 
            : 0,
            // 'Flash message' untuk Pop-up Modal Sukses
            'flash' => [
                'success' => fn () => $request->session()->get('toast_success'),
                'error' => fn () => $request->session()->get('toast_error'),
                // 'midtrans_redirect_url' => fn () => $request->session()->get('midtrans_redirect_url'),
                'snap_token' => fn () => $request->session()->get('snap_token'),
            ],

            'auth' => [
                'user' => $request->user(),
                // HAPUS wishlistCount dari sini, sudah ada di atas
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}