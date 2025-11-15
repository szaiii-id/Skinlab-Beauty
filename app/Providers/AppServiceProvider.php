<?php

namespace App\Providers;

use App\Models\PromoBanner;
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
        // Method 1: Replace the default Registered event handler
        Event::listen(Registered::class, function ($event) {
            // Do nothing - completely disable Laravel's auto email verification
            // Our custom system in CreateNewUser will handle everything
            Log::info('Auto email verification disabled - using custom system', [
                'user_id' => $event->user->id,
                'email' => $event->user->email
            ]);
        });
        JsonResource::withoutWrapping();
        PromoBanner::observe(\App\Observers\PromoBannerObserver::class);

        // ✅ Method 2: Alternative approach - forget any existing listeners
        // Event::forget(Registered::class);
    }
}