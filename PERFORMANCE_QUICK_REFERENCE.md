# Performance Optimization Quick Reference

## Essential Commands

### Analyze Performance
```bash
php artisan optimize:performance --analyze
```

### Clear Caches
```bash
php artisan optimize:performance --clear-cache
```

### Check Database Indexes
```bash
php artisan optimize:performance --check-indexes
```

### Run Tests
```bash
php artisan test tests/Feature/PerformanceOptimizationTest.php
```

---

## Using the Performance Service

### Inject the Service
```php
use App\Services\PerformanceService;

public function __construct(private PerformanceService $performance) {}
```

### Cache Query Results
```php
$user = $this->performance->remember(
    tag: 'user',
    key: $id,
    callback: fn() => User::find($id),
    dataType: 'user_data'  // 30-min TTL
);
```

### Optimize Queries
```php
$users = $this->performance->optimizeQuery(
    query: User::query(),
    relations: ['domains', 'emails'],
    columns: ['id', 'name', 'email']
)->get();
```

### Process Large Datasets
```php
$this->performance->processInChunks(
    query: User::query(),
    callback: fn($user) => $user->update(['status' => 'active']),
    chunkSize: 1000
);
```

### Monitor Query Performance
```php
$result = $this->performance->monitorQuery(
    callback: fn() => User::with('domains')->get(),
    name: 'Get Users with Domains'
);
```

### Paginate with Caching
```php
$users = $this->performance->paginateWithCache(
    query: User::query(),
    perPage: 20,
    cachePath: 'users',
    cacheMinutes: 30
);
```

---

## Using Model Caching

### Add Trait to Model
```php
use App\Traits\CacheableModel;

class User extends Model {
    use CacheableModel;
    
    protected string $cacheTag = 'user';
    protected int $cacheTtl = 1800;  // 30 minutes
}
```

### Use Cached Queries
```php
// Find single user with cache
$user = User::findCached($id);

// Get all users with cache
$users = User::getAllCached();

// Clear specific cache
User::clearCacheForId($id);

// Clear all user cache
$user->clearCache();
```

---

## Configuration

### Enable/Disable Caching
```php
// config/optimization.php
'caching' => [
    'enabled' => true,  // Toggle caching
    'driver' => 'redis',  // redis, database, file
    'default_ttl' => 3600,  // seconds
]
```

### Set Data Type TTLs
```php
// config/optimization.php
'ttls' => [
    'user_data' => 30,              // minutes
    'domains' => 20,
    'ssl_certificates' => 60,
    'databases' => 30,
    'email_accounts' => 20,
    'firewall_rules' => 15,
    'monitoring_data' => 5,
    'statistics' => 10,
]
```

### Database Optimization
```php
// config/optimization.php
'database' => [
    'log_slow_queries' => true,
    'slow_query_threshold_ms' => 1000,
    'eager_load_relations' => true,
    'prevent_n_plus_one' => true,
]
```

### Enable Performance Monitoring
```env
# .env
PERFORMANCE_MONITORING_ENABLED=true
DB_LOG_SLOW_QUERIES=true
```

---

## Cache Tags

Available cache tags for manual invalidation:

```
user        - User data
domain      - Web domains
ssl         - SSL certificates
database    - Databases
email       - Email accounts
```

### Manual Cache Control
```php
// Forget specific entry
$this->performance->forget('user', '123');

// Forget all of tag
$this->performance->forget('user');

// Clear all caches
$this->performance->clearAllCaches();
```

---

## Performance Alert Thresholds

```php
// config/optimization.php
'alert_thresholds' => [
    'slow_request_ms' => 1000,        // 1 second
    'high_memory_mb' => 128,          // 128 MB
    'excessive_queries' => 50,        // 50 queries
    'low_cache_hit_ratio' => 0.5,    // 50%
]
```

---

## Best Practices Checklist

- [ ] Enable caching in production
- [ ] Use appropriate cache driver (Redis recommended)
- [ ] Add indexes to frequently filtered columns
- [ ] Always use eager loading with relations
- [ ] Set realistic cache TTLs
- [ ] Monitor slow queries regularly
- [ ] Use pagination for large result sets
- [ ] Enable query logging in development
- [ ] Test performance with realistic data
- [ ] Review performance logs weekly

---

## Common Use Cases

### User Authentication (Controller)
```php
$user = $this->performance->remember(
    tag: 'user',
    key: auth()->id(),
    callback: fn() => User::with('roles', 'permissions')->find(auth()->id()),
    dataType: 'user_data'
);
```

### Dashboard Statistics
```php
$stats = $this->performance->remember(
    tag: 'stats',
    key: 'dashboard:' . auth()->id(),
    callback: fn() => [
        'domains' => User::find(auth()->id())->domains()->count(),
        'emails' => User::find(auth()->id())->emails()->count(),
    ],
    dataType: 'statistics'
);
```

### Large Report Generation
```php
$data = [];
$this->performance->processInChunks(
    query: AuditLog::whereYear('created_at', 2024)->query(),
    callback: function ($log) use (&$data) {
        $data[] = $log->toArray();
    },
    chunkSize: 5000
);
```

### Search Results
```php
$results = $this->performance->paginateWithCache(
    query: User::where('status', 'active')->search($term),
    perPage: 30,
    cachePath: "search:{$term}",
    cacheMinutes: 15
);
```

---

## Troubleshooting

### Cache Not Working
- Check `CACHE_DRIVER` in `.env`
- Verify `PERFORMANCE_CACHING_ENABLED=true`
- Run `php artisan cache:clear`
- Check Redis connection if using Redis

### Slow Queries
- Check database indexes with `php artisan optimize:performance --check-indexes`
- Review slow query logs in `storage/logs/performance.log`
- Enable query debugging: `APP_DEBUG=true`
- Check query count in performance middleware headers

### High Memory Usage
- Increase chunk size for batch operations
- Enable eager loading to prevent N+1
- Check for unbounded queries
- Monitor with `php artisan optimize:performance --analyze`

### Missing Indexes
- Run `php artisan migrate` to create indexes
- Check migration ran successfully
- Verify with `php artisan optimize:performance --check-indexes`

---

## Performance Metrics

### Response Time Header
```
X-Execution-Time: 123.45ms
```

### Memory Usage Header
```
X-Memory-Usage: 12.34MB
```

### Performance Log Format
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

---

## Performance Targets

- Request response time: **< 500ms**
- Database queries per request: **< 10**
- Memory per request: **< 64MB**
- Cache hit ratio: **> 70%**
- Page load time: **< 3 seconds**

---

## Documentation

- Full guide: `PERFORMANCE_OPTIMIZATION.md`
- Summary: `PERFORMANCE_OPTIMIZATION_SUMMARY.md`
- Configuration: `config/optimization.php`
- Service: `app/Services/PerformanceService.php`
- Tests: `tests/Feature/PerformanceOptimizationTest.php`

---

## Support

For detailed information, refer to:
1. Laravel Cache Documentation: https://laravel.com/docs/12/cache
2. Query Optimization: https://laravel.com/docs/12/eloquent#eager-loading
3. Database Indexes: https://laravel.com/docs/12/migrations#indexes
4. Performance Monitoring: https://laravel.com/docs/12/logging
