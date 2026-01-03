<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis as RedisFacade;

class RedisService
{
    /**
     * Get Redis client instance.
     *
     * @return mixed
     */
    public function client(string $connection = 'default')
    {
        return RedisFacade::connection($connection)->client();
    }

    /**
     * Set a key-value pair.
     */
    public function set(string $key, mixed $value, ?int $ttl = null, string $connection = 'default'): bool
    {
        $redis = RedisFacade::connection($connection);

        if ($ttl !== null) {
            $redis->setex($key, $ttl, json_encode($value));
        } else {
            $redis->set($key, json_encode($value));
        }

        return true;
    }

    /**
     * Get a value by key.
     */
    public function get(string $key, string $connection = 'default'): mixed
    {
        $redis = RedisFacade::connection($connection);
        $value = $redis->get($key);

        if ($value === null) {
            return null;
        }

        return json_decode($value, associative: true);
    }

    /**
     * Delete a key.
     */
    public function delete(string $key, string $connection = 'default'): int
    {
        return RedisFacade::connection($connection)->del($key);
    }

    /**
     * Check if a key exists.
     */
    public function exists(string $key, string $connection = 'default'): bool
    {
        return RedisFacade::connection($connection)->exists($key) === 1;
    }

    /**
     * Get all keys matching a pattern.
     */
    public function keys(string $pattern = '*', string $connection = 'default'): array
    {
        return RedisFacade::connection($connection)->keys($pattern);
    }

    /**
     * Get database size (number of keys).
     */
    public function dbSize(string $connection = 'default'): int
    {
        return RedisFacade::connection($connection)->dbSize();
    }

    /**
     * Get server info.
     */
    public function info(string $section = 'default', string $connection = 'default'): array
    {
        $redis = RedisFacade::connection($connection);
        $info = $redis->info($section);

        if (is_string($info)) {
            $parsed = [];
            foreach (explode("\r\n", $info) as $line) {
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                [$key, $value] = explode(':', $line, 2) + ['', ''];
                $parsed[$key] = $value;
            }

            return $parsed;
        }

        return is_array($info) ? $info : [];
    }

    /**
     * Flush database.
     */
    public function flushDb(string $connection = 'default'): bool
    {
        RedisFacade::connection($connection)->flushDb();

        return true;
    }

    /**
     * Flush all databases.
     */
    public function flushAll(): bool
    {
        RedisFacade::flushAll();

        return true;
    }

    /**
     * Get key details (type, ttl, memory usage).
     */
    public function keyDetails(string $key, string $connection = 'default'): array
    {
        $redis = RedisFacade::connection($connection);

        return [
            'key' => $key,
            'exists' => $redis->exists($key) === 1,
            'type' => $redis->type($key),
            'ttl' => max(-1, $redis->ttl($key)),
            'memory' => $redis->memory('usage', $key) ?? 0,
        ];
    }

    /**
     * Set key expiration.
     */
    public function expire(string $key, int $ttl, string $connection = 'default'): bool
    {
        return RedisFacade::connection($connection)->expire($key, $ttl) === 1;
    }

    /**
     * Get memory stats.
     */
    public function memoryStats(string $connection = 'default'): array
    {
        $redis = RedisFacade::connection($connection);
        $stats = $redis->memory('stats');

        if (is_array($stats)) {
            return $stats;
        }

        $info = $this->info('memory', $connection);

        return [
            'used_memory' => $info['used_memory'] ?? 0,
            'used_memory_human' => $info['used_memory_human'] ?? 'N/A',
            'used_memory_rss' => $info['used_memory_rss'] ?? 0,
            'used_memory_peak' => $info['used_memory_peak'] ?? 0,
            'used_memory_dataset' => $info['used_memory_dataset'] ?? 0,
        ];
    }

    /**
     * Ping Redis server.
     */
    public function ping(string $connection = 'default'): bool
    {
        try {
            RedisFacade::connection($connection)->ping();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Monitor commands in real-time.
     */
    public function monitor(string $connection = 'default'): void
    {
        RedisFacade::connection($connection)->monitor();
    }
}
