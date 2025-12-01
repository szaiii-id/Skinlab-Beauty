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

        if (str_contains(request()->getHost(), 'ngrok-free.app')) {
            URL::forceScheme('https');
        }
        
        JsonResource::withoutWrapping();

        PromoBanner::observe(PromoBannerObserver::class);
        Product::observe(ProductObserver::class);
        ProductVariant::observe(VariantObserver::class);
        Brand::observe(BrandObserver::class);
        Category::observe(CategoryObserver::class);
    }
}