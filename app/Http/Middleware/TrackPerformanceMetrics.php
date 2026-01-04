<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackPerformanceMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('optimization.monitoring.enabled')) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $startPeakMemory = memory_get_peak_usage();

        // Count database queries
        $queryCount = 0;
        if (config('optimization.monitoring.track_database_queries')) {
            DB::listen(function () use (&$queryCount) {
                $queryCount++;
            });
        }

        $response = $next($request);

        $executionTime = (microtime(true) - $startTime) * 1000;
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024;
        $peakMemoryUsed = (memory_get_peak_usage() - $startPeakMemory) / 1024 / 1024;

        // Log performance metrics
        if (config('optimization.monitoring.log_performance_data')) {
            Log::channel('performance')->info('Request Performance', [
                'path' => $request->path(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'memory_used_mb' => round($memoryUsed, 2),
                'peak_memory_mb' => round($peakMemoryUsed, 2),
                'database_queries' => $queryCount,
                'status_code' => $response->getStatusCode(),
            ]);
        }

        // Add performance headers
        $response->header('X-Execution-Time', round($executionTime, 2).'ms');
        $response->header('X-Memory-Usage', round($memoryUsed, 2).'MB');

        return $response;
    }
}
