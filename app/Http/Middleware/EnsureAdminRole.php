<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  (List of allowed roles)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Check if user is authenticated
        if (! $request->user()) {
            // If not logged in, redirect to login page
            return redirect()->route('admin.login');
        }

        // 2. Check if the user's role is allowed
        // We check if the current user's role exists in the required $roles list.
        if (! in_array($request->user()->role, $roles)) {
            
            // If the role is NOT allowed, show 403 Forbidden error
            abort(403, 'UNAUTHORIZED ACCESS: Your role (' . $request->user()->role . ') is not permitted to view this page.');
        }

        // 3. If allowed, proceed to the page
        return $next($request);
    }
}