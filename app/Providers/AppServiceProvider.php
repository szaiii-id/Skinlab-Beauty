<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request; 

// Models & Observers
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoBanner;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use App\Observers\PromoBannerObserver;
use App\Observers\VariantObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // 1. TRUST PROXIES (WAJIB AGAR NGROK TERBACA HTTPS)
        Request::setTrustedProxies(
            ['*'], 
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        Event::listen(Registered::class, function ($event) {
            Log::info('Auto verification disabled', ['user_id' => $event->user->id]);
        });
        JsonResource::withoutWrapping();
        PromoBanner::observe(PromoBannerObserver::class);
        Product::observe(ProductObserver::class);
        ProductVariant::observe(VariantObserver::class);
        Brand::observe(BrandObserver::class);
        Category::observe(CategoryObserver::class);

        // =================================================================
        // 🔥 LOGIKA OTOMATIS: LOCALHOST vs NGROK 🔥
        // =================================================================
        if (!app()->runningInConsole()) {
            $request = request();
            $host = $request->getHost();
            
            // Cek apakah ini Ngrok?
            $isNgrok = Str::contains($host, ['ngrok-free.dev', 'ngrok-free.app']);

            if ($isNgrok) {
                // === KONDISI 1: NGROK (HTTPS) ===
                
                // A. Paksa URL HTTPS
                URL::forceScheme('https');
                $this->app['request']->server->set('HTTPS', 'on');

                // B. Paksa Config URL ke Ngrok
                $ngrokUrl = 'https://' . $host;
                config([
                    'app.url' => $ngrokUrl,
                    'app.asset_url' => $ngrokUrl,
                    'filesystems.disks.public.url' => $ngrokUrl . '/storage',
                ]);

                // C. SETUP COOKIE KHUSUS NGROK (SOLUSI FCM 401)
                config([
                    'session.secure' => true,     // <--- WAJIB TRUE DI NGROK
                    'session.same_site' => 'lax', 
                    'session.domain' => null,     // Biarkan browser atur domain
                ]);

                // D. Whitelist Sanctum
                $currentSanctum = config('sanctum.stateful', []);
                if (is_string($currentSanctum)) $currentSanctum = explode(',', $currentSanctum);
                $currentSanctum[] = $host;
                config(['sanctum.stateful' => array_values(array_unique($currentSanctum))]);
            
            } else {
                // === KONDISI 2: LOCALHOST (HTTP) ===
                
                // Pastikan secure cookie MATI di localhost agar tidak error 419
                config([
                    'session.secure' => false, 
                ]);
            }
        }
    }
}