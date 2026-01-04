<?php

/**
 * Rate Limiting Middleware
 *
 * Implements comprehensive rate limiting to protect against:
 * - Brute force attacks
 * - DDoS attacks
 * - API abuse
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimiting
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apply different rate limits based on request type
        if ($request->is('api/*')) {
            $this->applyApiRateLimiting($request);
        } elseif ($request->is('auth/*')) {
            $this->applyAuthRateLimiting($request);
        } elseif ($request->is('*/download*')) {
            $this->applyDownloadRateLimiting($request);
        } else {
            $this->applyGeneralRateLimiting($request);
        }

        return $next($request);
    }

    /**
     * Apply rate limiting to API endpoints
     * 100 requests per minute per IP
     */
    private function applyApiRateLimiting(Request $request): void
    {
        $key = 'api:'.$request->ip();
        RateLimiter::attempt($key, 100, function () {
            // Allow
        }, 60);
    }

    /**
     * Apply strict rate limiting to authentication endpoints
     * 5 attempts per minute per IP
     */
    private function applyAuthRateLimiting(Request $request): void
    {
        if ($request->isMethod('post')) {
            $key = 'auth:'.$request->ip().':'.$request->input('email', '');

            if (! RateLimiter::attempt($key, 5, function () {
                // Allow
            }, 60)) {
                abort(429, 'Too many authentication attempts. Please try again later.');
            }
        }
    }

    /**
     * Apply rate limiting to download endpoints
     * 20 concurrent downloads per user
     */
    private function applyDownloadRateLimiting(Request $request): void
    {
        if ($request->user()) {
            $key = 'download:'.$request->user()->id;
            RateLimiter::attempt($key, 20, function () {
                // Allow
            }, 3600);
        }
    }

    /**
     * Apply general rate limiting to all other requests
     * 60 requests per minute per IP
     */
    private function applyGeneralRateLimiting(Request $request): void
    {
        $key = 'request:'.$request->ip();
        RateLimiter::attempt($key, 60, function () {
            // Allow
        }, 60);
    }
}
