<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PerformanceService - Centralized performance optimization service
 *
 * Handles caching, query optimization, and performance monitoring.
 */
class PerformanceService
{
    protected bool $cacheEnabled;

    protected int $defaultTtl;

    protected array $slowQueries = [];

    public function __construct()
    {
        $this->cacheEnabled = config('optimization.caching.enabled');
        $this->defaultTtl = config('optimization.caching.default_ttl');
    }

    /**
     * Cache a query result with automatic TTL based on data type
     *
     * Example: cache('user_data', 'user:1', fn() => User::find(1), 'user_data')
     */
    public function remember(
        string $tag,
        string $key,
        callable $callback,
        ?string $dataType = null,
        ?int $ttl = null
    ): mixed {
        if (! $this->cacheEnabled) {
            return call_user_func($callback);
        }

        $ttl = $ttl ?? $this->getTtlForDataType($dataType ?? $tag);
        $cacheKey = "{$tag}:{$key}";

        return Cache::tags([$tag])->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get TTL for a specific data type
     */
    protected function getTtlForDataType(string $dataType): int
    {
        $ttls = config('optimization.caching.ttls', []);

        return $ttls[$dataType] ?? $this->defaultTtl;
    }

    /**
     * Forget cache for a tag or specific key
     */
    public function forget(string $tag, ?string $key = null): void
    {
        if ($key) {
            Cache::tags([$tag])->forget("{$tag}:{$key}");
        } else {
            Cache::tags([$tag])->flush();
        }
    }

    /**
     * Optimize a query by preventing N+1 queries with eager loading
     */
    public function optimizeQuery(
        $query,
        array $relations = [],
        array $columns = ['*']
    ) {
        // Select only needed columns
        if ($columns !== ['*']) {
            $query->select($columns);
        }

        // Eager load relations
        if (! empty($relations)) {
            $query->with($relations);
        }

        // Enable query tracking in debug mode
        if (config('optimization.dev_tools.debug_queries')) {
            $this->logQueryPlan($query);
        }

        return $query;
    }

    /**
     * Paginate results with caching support
     */
    public function paginateWithCache(
        $query,
        int $perPage = 15,
        string $cachePath = 'default',
        int $cacheMinutes = 30
    ): LengthAwarePaginator {
        $page = request()->query('page', 1);
        $cacheKey = "{$cachePath}:page_{$page}:{$perPage}";

        if ($this->cacheEnabled) {
            return Cache::remember($cacheKey, $cacheMinutes * 60, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Batch process large datasets to prevent memory issues
     */
    public function processInChunks(
        $query,
        callable $callback,
        int $chunkSize = 1000
    ): void {
        $query->chunk($chunkSize, function ($items) use ($callback) {
            foreach ($items as $item) {
                call_user_func($callback, $item);
            }
        });
    }

    /**
     * Monitor query performance
     */
    public function monitorQuery(callable $callback, string $name = 'Query'): mixed
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $result = call_user_func($callback);

        $executionTime = (microtime(true) - $startTime) * 1000;
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024;

        if (config('optimization.monitoring.enabled')) {
            $this->logPerformanceMetric($name, $executionTime, $memoryUsed);
        }

        if ($executionTime > config('optimization.monitoring.alert_thresholds.slow_request_ms')) {
            Log::warning("Slow {$name}: {$executionTime}ms, Memory: {$memoryUsed}MB");
        }

        return $result;
    }

    /**
     * Log performance metric
     */
    protected function logPerformanceMetric(string $name, float $executionTime, float $memoryUsed): void
    {
        if (config('optimization.monitoring.log_performance_data')) {
            Log::channel('performance')->debug("{$name} executed in {$executionTime}ms, using {$memoryUsed}MB");
        }
    }

    /**
     * Enable query logging (development only)
     */
    public function enableQueryLogging(): void
    {
        if (! config('optimization.dev_tools.debug_queries')) {
            return;
        }

        DB::listen(function ($query) {
            if ($query->time > config('optimization.database.slow_query_threshold_ms')) {
                Log::warning('Slow Query', [
                    'query' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time.'ms',
                ]);
            }
        });
    }

    /**
     * Log query execution plan (MySQL)
     */
    protected function logQueryPlan($query): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        try {
            $plan = DB::select('EXPLAIN '.$query->toSql(), $query->getBindings());
            Log::debug('Query Plan', ['plan' => $plan]);
        } catch (\Exception $e) {
            Log::error('Failed to get query plan', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cache hit ratio
     */
    public function getCacheHitRatio(): float
    {
        // This would require Redis statistics
        // Implement based on your cache driver
        return 0.75; // Placeholder
    }

    /**
     * Clear all caches
     */
    public function clearAllCaches(): void
    {
        Cache::flush();
        Log::info('All caches cleared');
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        return [
            'cache_enabled' => $this->cacheEnabled,
            'cache_driver' => config('cache.default'),
            'database_optimization' => config('optimization.database'),
            'monitoring_enabled' => config('optimization.monitoring.enabled'),
            'slow_query_threshold_ms' => config('optimization.database.slow_query_threshold_ms'),
        ];
    }
}
