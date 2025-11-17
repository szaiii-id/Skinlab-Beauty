<?php

namespace App\Providers;

use App\Models\PromoBanner;
use App\Models\UserAddress;
use App\Repositories\AddressRepository;
use App\Services\AddressService;
use App\Services\OpenStreetMapService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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
        
        JsonResource::withoutWrapping();
        PromoBanner::observe(\App\Observers\PromoBannerObserver::class);
    }
}