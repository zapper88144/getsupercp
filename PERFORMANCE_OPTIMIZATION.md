# Performance Optimization Guide

This guide covers implementing and managing the advanced performance optimizations in GetSuperCP.

## Table of Contents

1. [Caching Strategy](#caching-strategy)
2. [Database Optimization](#database-optimization)
3. [Query Optimization](#query-optimization)
4. [Frontend Optimization](#frontend-optimization)
5. [Monitoring](#monitoring)
6. [Best Practices](#best-practices)

## Caching Strategy

### Configuration

Edit your `.env` file to configure caching:

```env
# Cache Driver (redis, database, array, file)
CACHE_DRIVER=redis

# Enable caching for performance
PERFORMANCE_CACHING_ENABLED=true

# Default cache TTL (seconds)
CACHE_DEFAULT_TTL=3600

# Monitor performance
PERFORMANCE_MONITORING_ENABLED=true
```

### Using the Performance Service

Inject and use the `PerformanceService` in your code:

```php
use App\Services\PerformanceService;

class UserController extends Controller
{
    public function __construct(private PerformanceService $performance) {}

    public function show($id)
    {
        // Cache user data for 30 minutes
        $user = $this->performance->remember(
            tag: 'user',
            key: $id,
            callback: fn() => User::with('domains')->find($id),
            dataType: 'user_data'
        );

        return view('user.show', ['user' => $user]);
    }
}
```

### Available Data Type TTLs

```
user_data          -> 30 minutes
domains            -> 20 minutes
ssl_certificates   -> 60 minutes
databases          -> 30 minutes
email_accounts     -> 20 minutes
firewall_rules     -> 15 minutes
monitoring_data    -> 5 minutes
statistics         -> 10 minutes
api_responses      -> 5 minutes
```

### Cache Invalidation

Automatically invalidated on model changes, or manually:

```php
use App\Services\PerformanceService;

// Forget specific cache entry
$this->performance->forget('user', '123');

// Forget all user cache
$this->performance->forget('user');

// Clear all caches
$this->performance->clearAllCaches();
```

## Database Optimization

### Performance Indexes

The migration `add_performance_indexes` creates optimal indexes for:

- User authentication (email, status)
- Domain lookups (user_id, domain_name, status)
- Email account filtering (user_id, domain_id, status)
- SSL certificate expiration tracking
- Audit log filtering and timeline queries
- Backup history and resource type queries

Run the migration:

```bash
php artisan migrate
```

### Eager Loading

Always eager load related data to prevent N+1 queries:

```php
// BAD: Causes N+1 queries
$users = User::all();
foreach ($users as $user) {
    echo $user->domains->count(); // Additional query per user
}

// GOOD: Single query with eager loading
$users = User::with('domains')->get();
```

### Using the Performance Service for Optimization

```php
// Optimize query with eager loading
$query = User::query();
$query = $this->performance->optimizeQuery(
    query: $query,
    relations: ['domains', 'emails'],
    columns: ['id', 'name', 'email']
);
$users = $query->get();
```

### Batch Processing Large Datasets

```php
// Process 10,000 users without memory issues
$this->performance->processInChunks(
    query: User::query(),
    callback: function ($user) {
        // Process user
    },
    chunkSize: 1000
);
```

## Query Optimization

### Selective Columns

Only select columns you need:

```php
// BAD: Selects all columns
User::all();

// GOOD: Select only needed columns
User::select(['id', 'name', 'email'])->get();
```

### Pagination with Caching

```php
// Paginate with automatic caching
$users = $this->performance->paginateWithCache(
    query: User::query(),
    perPage: 20,
    cachePath: 'users',
    cacheMinutes: 30
);
```

### Query Monitoring

```php
// Monitor query execution time and memory
$result = $this->performance->monitorQuery(
    callback: fn() => User::with('domains')->limit(100)->get(),
    name: 'Get Users with Domains'
);
```

### Slow Query Logging

Configure in `.env`:

```env
DB_LOG_SLOW_QUERIES=true
DB_SLOW_QUERY_THRESHOLD_MS=1000
```

Slow queries are logged to `storage/logs/performance.log`.

## Frontend Optimization

### Asset Optimization

Configure in `config/optimization.php`:

```php
'frontend' => [
    'minify_css' => true,
    'minify_javascript' => true,
    'lazy_load_images' => true,
    'optimize_images' => true,
    'critical_css_enabled' => true,
    'service_worker_enabled' => true,
]
```

### Vite Configuration

Ensure your `vite.config.js` has:

```javascript
// Build optimization
build: {
  minify: 'terser',
  terserOptions: {
    compress: {
      drop_console: process.env.NODE_ENV === 'production',
    },
  },
}
```

### Using Cacheable Models

Apply the `CacheableModel` trait to frequently accessed models:

```php
use App\Traits\CacheableModel;

class User extends Model
{
    use CacheableModel;
    
    protected string $cacheTag = 'user';
    protected int $cacheTtl = 1800; // 30 minutes
}

// Use cached queries
$user = User::findCached($id);
$users = User::getAllCached();
User::clearCacheForId($id);
```

## Monitoring

### Performance Middleware

The `TrackPerformanceMetrics` middleware automatically tracks:

- Request execution time
- Memory usage
- Peak memory usage
- Database query count
- Status code

Register in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(TrackPerformanceMetrics::class);
})
```

### Performance Headers

Each response includes performance headers:

```
X-Execution-Time: 123.45ms
X-Memory-Usage: 12.34MB
```

### Performance Logs

Configure logging in `.env`:

```env
PERFORMANCE_MONITORING_ENABLED=true
APP_DEBUG=true
```

View logs in `storage/logs/performance.log`:

```
[2024-01-15 10:30:45] local.INFO: Request Performance {
  "path": "/users",
  "method": "GET",
  "execution_time_ms": 245.67,
  "memory_used_mb": 8.45,
  "peak_memory_mb": 15.32,
  "database_queries": 3,
  "status_code": 200
}
```

### Alert Thresholds

Configure in `config/optimization.php`:

```php
'alert_thresholds' => [
    'slow_request_ms' => 1000,        // Alert if request > 1 second
    'high_memory_mb' => 128,          // Alert if memory > 128 MB
    'excessive_queries' => 50,        // Alert if > 50 queries
    'low_cache_hit_ratio' => 0.5,    // Alert if hit ratio < 50%
]
```

## Best Practices

### 1. Cache Strategy

- **Always cache frequently accessed data** (users, configurations)
- **Cache results of expensive queries** (reports, statistics)
- **Use appropriate TTLs** based on data type
- **Monitor cache hit ratios** to optimize TTLs

### 2. Database Practices

- **Use indexes** on frequently filtered columns
- **Enable eager loading** to prevent N+1 queries
- **Paginate large result sets** to limit memory usage
- **Log and analyze** slow queries
- **Use transactions** for data consistency
- **Denormalize when needed** for read-heavy operations

### 3. Query Optimization

```php
// GOOD: Optimized query
User::with(['domains', 'emails'])
    ->select(['id', 'name', 'email'])
    ->where('status', 'active')
    ->paginate(20);

// BAD: Unoptimized
User::all();
```

### 4. Monitoring

- **Enable performance monitoring** in production
- **Set appropriate alert thresholds**
- **Review logs regularly** for bottlenecks
- **Track cache hit ratios**
- **Monitor database query performance**

### 5. Frontend Optimization

- **Minify CSS and JavaScript**
- **Lazy load images**
- **Compress assets with gzip**
- **Use service workers** for caching
- **Preload critical assets**
- **Use modern image formats** (WebP)

### 6. Code Review Checklist

- [ ] Are all database queries eager loading relations?
- [ ] Are indexes created for filtered/sorted columns?
- [ ] Is frequently accessed data cached?
- [ ] Are large datasets paginated or chunked?
- [ ] Are appropriate TTLs configured?
- [ ] Is N+1 query prevention enabled?
- [ ] Are slow queries logged and monitored?
- [ ] Is frontend asset optimization enabled?

## Testing Performance

### Test Cached Queries

```php
public function test_user_cache()
{
    $user = User::factory()->create();
    
    // First query hits database
    $cached = User::findCached($user->id);
    $this->assertEquals($user->id, $cached->id);
    
    // Second query hits cache
    // (Verify with query logging)
}
```

### Test Performance Middleware

```php
public function test_performance_headers()
{
    $response = $this->get('/');
    
    $this->assertTrue($response->hasHeader('X-Execution-Time'));
    $this->assertTrue($response->hasHeader('X-Memory-Usage'));
}
```

### Benchmark Queries

```bash
# Enable query logging
php artisan tinker

>>> DB::enableQueryLog();
>>> User::with('domains')->get();
>>> dd(DB::getQueryLog());
```

## Troubleshooting

### High Memory Usage

1. Check if chunking is used for large datasets
2. Ensure eager loading prevents N+1 queries
3. Monitor memory limit in `.env`
4. Review slow query logs

### Slow Requests

1. Check database indexes are created
2. Enable query logging in development
3. Review for N+1 queries
4. Check cache hit ratios

### Cache Issues

1. Verify cache driver is Redis, not array
2. Check cache TTLs are appropriate
3. Review cache invalidation logic
4. Monitor cache memory usage

## Additional Resources

- [Laravel Caching Documentation](https://laravel.com/docs/12/cache)
- [Laravel Query Optimization](https://laravel.com/docs/12/eloquent#eager-loading)
- [Laravel Database Indexes](https://laravel.com/docs/12/migrations#indexes)
- [Performance Monitoring Best Practices](https://laravel.com/docs/12/logging)
