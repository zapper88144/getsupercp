<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerifyPhpMyAdminAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if phpMyAdmin is enabled
        if (! config('phpmyadmin.enabled')) {
            Log::warning('phpMyAdmin access attempted but feature is disabled', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            abort(404, 'phpMyAdmin is not available');
        }

        // Check if user is authenticated
        if (! Auth::check()) {
            Log::warning('Unauthenticated access attempt to phpMyAdmin', [
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login');
        }

        // Check if user is admin
        if (! Auth::user()->is_admin) {
            Log::warning('Non-admin user attempted to access phpMyAdmin', [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'ip' => $request->ip(),
            ]);

            abort(403, 'Unauthorized: Admin privileges required');
        }

        // Check IP restrictions if configured
        $allowedIps = config('phpmyadmin.security.allowed_ips');
        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps)) {
            Log::warning('Access from unauthorized IP to phpMyAdmin', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'allowed_ips' => implode(',', $allowedIps),
            ]);

            abort(403, 'Access denied from your IP address');
        }

        // Log successful access
        Log::info('phpMyAdmin access granted', [
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        // Add security headers
        $response = $next($request);

        return $response
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
