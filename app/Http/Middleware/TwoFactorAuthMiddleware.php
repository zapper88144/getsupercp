<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Exclude 2FA challenge and setup routes from enforcement
        if ($request->routeIs('two-factor.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        if ($user && $user->twoFactorAuthentication && $user->twoFactorAuthentication->is_enabled) {
            if (! $request->session()->has('2fa_verified_at')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Two-factor authentication required.'], 403);
                }

                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
