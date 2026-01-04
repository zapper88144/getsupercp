<?php

/**
 * Security Hardening Middleware
 *
 * Implements comprehensive security headers and protections:
 * - HSTS (HTTP Strict Transport Security)
 * - CSP (Content Security Policy)
 * - X-Frame-Options (Clickjacking protection)
 * - X-Content-Type-Options (MIME-type sniffing prevention)
 * - X-XSS-Protection (XSS protection)
 * - Referrer-Policy
 * - Permissions-Policy
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // HSTS - Force HTTPS for 1 year (31536000 seconds)
        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Content Security Policy - Strict by default
        $scriptSrc = "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net";
        $connectSrc = "connect-src 'self' https:";

        // Allow Vite dev server in local environment
        if (app()->environment('local')) {
            $scriptSrc .= ' http://127.0.0.1:4000 http://localhost:4000';
            $connectSrc .= ' http://127.0.0.1:4000 http://localhost:4000 ws://127.0.0.1:4000 ws://localhost:4000';
        }

        $cspList = [
            "default-src 'self'",
            $scriptSrc,
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.bunny.net",
            "img-src 'self' data: https:",
            "font-src 'self' data: cdn.jsdelivr.net fonts.bunny.net",
            $connectSrc,
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        if (! app()->environment('local')) {
            $cspList[] = 'upgrade-insecure-requests';
        }

        $csp = implode('; ', $cspList);
        $response->header('Content-Security-Policy', $csp);

        // Prevent clickjacking attacks
        $response->header('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection in older browsers
        $response->header('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features and APIs
        $response->header('Permissions-Policy', implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()',
        ]));

        // Remove server information from response
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        // Add security-related custom headers
        $response->header('X-Content-Security-Policy-Report-Only', $csp);
        $response->header('X-UA-Compatible', 'IE=edge');

        return $response;
    }
}
