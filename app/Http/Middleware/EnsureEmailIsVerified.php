<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->hasVerifiedEmail()) {
            Auth::logout();
            
            return redirect()->route('login')
                ->with('error', 'You need to verify your email address before accessing the dashboard.');
        }

        return $next($request);
    }
}