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
        } elseif ($request->is('login') || $request->is('register') || $request->is('two-factor-*') || $request->is('forgot-password') || $request->is('reset-password')) {
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
        if (! RateLimiter::attempt($key, 100, function () {
            // Allow
        }, 60)) {
            \App\Models\AuditLog::log(
                action: 'rate_limit_api',
                description: 'API rate limit exceeded for IP: '.$request->ip(),
                result: 'failed'
            );
            abort(429, 'Too many API requests. Please try again later.');
        }
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
                \App\Models\AuditLog::log(
                    action: 'rate_limit_auth',
                    description: 'Authentication rate limit exceeded for IP: '.$request->ip().' and email: '.$request->input('email', ''),
                    result: 'failed'
                );
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
        $key = $request->user() ? 'download:'.$request->user()->id : 'download:'.$request->ip();

        if (! RateLimiter::attempt($key, 20, function () {
            // Allow
        }, 3600)) {
            abort(429, 'Too many download requests. Please try again later.');
        }
    }

    /**
     * Apply general rate limiting to all other requests
     * 60 requests per minute per IP
     */
    private function applyGeneralRateLimiting(Request $request): void
    {
        $key = 'request:'.$request->ip();
        if (! RateLimiter::attempt($key, 60, function () {
            // Allow
        }, 60)) {
            abort(429, 'Too many requests. Please try again later.');
        }
    }
}
