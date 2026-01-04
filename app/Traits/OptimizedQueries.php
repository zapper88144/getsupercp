<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides query optimization utilities for Eloquent models.
 *
 * Includes methods for selective column loading, eager loading strategies,
 * and query performance best practices.
 */
trait OptimizedQueries
{
    /**
     * Select only essential columns for list views
     */
    public function scopeSelectEssential(Builder $query): Builder
    {
        return $query->select(
            'id',
            'created_at',
            'updated_at'
        );
    }

    /**
     * Optimize query for user-scoped resources
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Eager load common relationships
     */
    public function scopeWithRelations(Builder $query): Builder
    {
        $relations = property_exists($this, 'eagerLoadRelations')
            ? $this->eagerLoadRelations
            : [];

        if (! empty($relations)) {
            return $query->with($relations);
        }

        return $query;
    }

    /**
     * Apply pagination with optimized query
     */
    public function scopeOptimizedPaginate(Builder $query, int $perPage = 15): mixed
    {
        return $query->select($this->getSelectableColumns())
            ->paginate($perPage);
    }

    /**
     * Get only active records
     */
    public function scopeActive(Builder $query): Builder
    {
        if ($this->hasColumn('is_active')) {
            return $query->where('is_active', true);
        }

        return $query;
    }

    /**
     * Order by most recent
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Limit results to specific number
     */
    public function scopeLimited(Builder $query, int $limit): Builder
    {
        return $query->limit($limit);
    }

    /**
     * Get selectable columns for this model
     */
    protected function getSelectableColumns(): array
    {
        return $this->getQuerySelectColumns() ?? ['*'];
    }

    /**
     * Check if table has a specific column
     */
    protected function hasColumn(string $column): bool
    {
        return in_array($column, $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable()));
    }

    /**
     * Get columns that should be selected in queries
     * Override in model classes as needed
     */
    public function getQuerySelectColumns(): ?array
    {
        return null;
    }
}
