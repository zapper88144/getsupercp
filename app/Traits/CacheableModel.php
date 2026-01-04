<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * CacheableModel - Trait for caching model data
 *
 * Add to any Eloquent model to enable automatic caching.
 *
 * Usage:
 * class User extends Model {
 *     use CacheableModel;
 *     protected string $cacheTag = 'user';
 *     protected int $cacheTtl = 3600;
 * }
 */
trait CacheableModel
{
    protected string $cacheTag = 'model';

    protected int $cacheTtl = 3600;

    protected static function bootCacheableModel(): void
    {
        static::created(function ($model) {
            $model->clearCache();
        });

        static::updated(function ($model) {
            $model->clearCache();
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }

    /**
     * Find model with caching
     */
    public static function findCached($id): ?self
    {
        $instance = new static;
        $cacheKey = "{$instance->cacheTag}:{$id}";

        return Cache::tags([$instance->cacheTag])->remember(
            $cacheKey,
            $instance->cacheTtl,
            fn () => static::find($id)
        );
    }

    /**
     * Get all models with caching
     */
    public static function getAllCached(): mixed
    {
        $instance = new static;
        $cacheKey = "{$instance->cacheTag}:all";

        return Cache::tags([$instance->cacheTag])->remember(
            $cacheKey,
            $instance->cacheTtl,
            fn () => static::all()
        );
    }

    /**
     * Clear cache for this model
     */
    public function clearCache(): void
    {
        Cache::tags([$this->cacheTag])->flush();
    }

    /**
     * Clear cache for specific ID
     */
    public static function clearCacheForId($id): void
    {
        $instance = new static;
        Cache::tags([$instance->cacheTag])->forget("{$instance->cacheTag}:{$id}");
    }

    /**
     * Get cache tag
     */
    public function getCacheTag(): string
    {
        return $this->cacheTag;
    }

    /**
     * Set cache TTL
     */
    public function setCacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;

        return $this;
    }
}
