<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Performance monitoring and optimization middleware.
 *
 * Tracks request/response metrics, optimizes queries, and identifies bottlenecks.
 */
class PerformanceOptimization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start timing
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Set up query logging in development
        if (config('app.debug')) {
            \Illuminate\Support\Facades\DB::enableQueryLog();
        }

        $response = $next($request);

        // Calculate metrics
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = (memory_get_usage(true) - $startMemory) / 1024 / 1024; // Convert to MB
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024; // Convert to MB

        // Get query count
        $queryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());

        // Add performance headers
        $response->headers->set('X-Response-Time', round($duration, 2).'ms');
        $response->headers->set('X-Memory-Used', round($memoryUsed, 2).'MB');
        $response->headers->set('X-Peak-Memory', round($peakMemory, 2).'MB');
        $response->headers->set('X-Query-Count', $queryCount);
        $response->headers->set('X-Cache-Prefix', config('cache.prefix'));

        // Log slow requests
        if ($duration > 1000) { // Over 1 second
            Log::warning('Slow request detected', [
                'path' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => round($duration, 2),
                'memory_mb' => round($memoryUsed, 2),
                'queries' => $queryCount,
            ]);
        }

        // Log excessive memory usage
        if ($peakMemory > 128) { // Over 128 MB
            Log::warning('High memory usage detected', [
                'path' => $request->path(),
                'peak_memory_mb' => round($peakMemory, 2),
                'queries' => $queryCount,
            ]);
        }

        // Log N+1 query problems
        if ($queryCount > 50) {
            Log::warning('Excessive query count detected', [
                'path' => $request->path(),
                'query_count' => $queryCount,
                'duration_ms' => round($duration, 2),
            ]);
        }

        return $response;
    }
}
