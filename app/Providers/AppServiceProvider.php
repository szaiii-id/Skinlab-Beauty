<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoBanner;
use App\Models\UserAddress;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use App\Observers\PromoBannerObserver;
use App\Observers\VariantObserver;
use App\Repositories\AddressRepository;
use App\Services\AddressService;
use App\Services\OpenStreetMapService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ DISABLE AUTO EMAIL VERIFICATION COMPLETELY
        Event::listen(Registered::class, function ($event) {
            Log::info('Auto email verification disabled - using custom system', [
                'user_id' => $event->user->id,
                'email' => $event->user->email
            ]);
        });

        if (!app()->runningInConsole()) {
            $host = request()->getHost();
            $scheme = request()->getScheme();
            $port = request()->getPort();
            
            // Susun URL saat ini
            $currentUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');

            // Update Config URL
            config(['app.url' => $currentUrl]);
            config(['app.asset_url' => $currentUrl]);
            config(['filesystems.disks.public.url' => $currentUrl . '/storage']);

            // --- PERBAIKAN FATAL SANCTUM (ARRAY, BUKAN STRING) ---
            // Ambil config saat ini
            $currentStateful = config('sanctum.stateful', []);
            
            // Normalisasi ke array jika ternyata string
            if (is_string($currentStateful)) {
                $currentStateful = explode(',', $currentStateful);
            }

            // Tambahkan domain saat ini ke array
            $currentStateful[] = $host;
            if ($port) {
                $currentStateful[] = $host . ':' . $port;
            }

            // PENTING: Simpan kembali sebagai ARRAY (Jangan di-implode/jadikan string)
            // Menggunakan array_values untuk reset index agar rapi
            config(['sanctum.stateful' => array_values(array_unique(array_filter($currentStateful)))]);
            // -----------------------------------------------------

            // Paksa HTTPS jika Ngrok
            if (str_contains($host, 'ngrok-free.dev') || str_contains($host, 'ngrok-free.app')) {
                URL::forceScheme('https');
                $this->app['request']->server->set('HTTPS', 'on');
            }
        }
        
        JsonResource::withoutWrapping();

        PromoBanner::observe(PromoBannerObserver::class);
        Product::observe(ProductObserver::class);
        ProductVariant::observe(VariantObserver::class);
        Brand::observe(BrandObserver::class);
        Category::observe(CategoryObserver::class);
    }
}