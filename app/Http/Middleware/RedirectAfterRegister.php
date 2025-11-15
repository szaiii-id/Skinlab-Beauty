<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAfterRegister
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Cek jika ini response dari register
        if ($request->route()->named('register') && $request->isMethod('post')) {
            if (session('verification_email')) {
                return redirect()->route('verification.notice');
            }
        }

        return $response;
    }
}