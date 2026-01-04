<?php

namespace App\Http\Middleware;

use App\Services\BruteForceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BruteForceMiddleware
{
    public function __construct(private BruteForceService $bruteForceService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $service = $request->route()?->getName() ?? 'http';

        // Check if IP is blocked
        if ($this->bruteForceService->isIpBlocked($ip, $service)) {
            return response()->json([
                'message' => 'Too many requests. Your IP has been temporarily blocked.',
                'retry_after' => 3600,
            ], 429);
        }

        // Check if IP is whitelisted
        if ($this->bruteForceService->isIpWhitelisted($ip)) {
            return $next($request);
        }

        return $next($request);
    }
}
