<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <--- Tambahan Penting

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', 
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'api/midtrans-callback',
        ]);
        
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureAdminRole::class,
        ]);
        
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        
        $middleware->web(append: [
            \App\Http\Middleware\HandleAppearance::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 1. REDIRECT JIKA TAMU MENCOBA MASUK HALAMAN PROTECTED
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('skinlab-center*')) {
                return route('admin.login'); 
            }
            return route('login'); 
        });

        // 2. REDIRECT JIKA ORANG YANG SUDAH LOGIN MENCOBA BUKA HALAMAN LOGIN (Fix Masalah Anda)
        $middleware->redirectUsersTo(function (Request $request) {
            // Jika yang login adalah Admin, lempar ke Dashboard Admin
            if (Auth::guard('admin')->check()) {
                return route('admin.dashboard');
            }
            // Default: Dashboard User
            return route('dashboard');
        });

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();