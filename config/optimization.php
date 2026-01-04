<?php

/**
 * Performance Optimization Configuration
 *
 * Centralized settings for caching, query optimization, and performance tuning.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Caching Strategy
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for different data types.
    |
    */
    'caching' => [
        'enabled' => env('PERFORMANCE_CACHING_ENABLED', true),
        'driver' => env('CACHE_DRIVER', 'database'),
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600),

        // Time-to-live for different data types (in minutes)
        'ttls' => [
            'user_data' => 30,           // User profiles, settings
            'domains' => 20,              // Web domains
            'ssl_certificates' => 60,     // SSL certs (change less often)
            'databases' => 30,            // Database info
            'email_accounts' => 20,       // Email accounts
            'firewall_rules' => 15,       // Firewall (frequently changing)
            'monitoring_data' => 5,       // Real-time monitoring
            'statistics' => 10,           // Dashboard stats
            'api_responses' => 5,         // API endpoint caching
        ],

        // Cache tags for selective invalidation
        'tags' => [
            'user' => 'user:{id}',
            'domain' => 'domain:{id}',
            'ssl' => 'ssl:{id}',
            'database' => 'database:{id}',
            'email' => 'email:{id}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Optimization
    |--------------------------------------------------------------------------
    |
    | Query optimization settings and indexes.
    |
    */
    'database' => [
        // Enable query optimization logging
        'log_slow_queries' => env('DB_LOG_SLOW_QUERIES', true),
        'slow_query_threshold_ms' => env('DB_SLOW_QUERY_THRESHOLD_MS', 1000),

        // Connection pooling (for MySQL/PostgreSQL)
        'connection_pool_size' => env('DB_POOL_SIZE', 10),
        'connection_max_lifetime' => env('DB_CONNECTION_MAX_LIFETIME', 3600),

        // Query optimization
        'use_prepared_statements' => true,
        'enable_query_builder' => true,
        'optimize_with_indexes' => true,

        // Batch operations
        'batch_insert_size' => 1000,
        'batch_update_size' => 500,

        // Eager loading defaults
        'eager_load_relations' => true,
        'prevent_n_plus_one' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Optimization
    |--------------------------------------------------------------------------
    |
    | Memory usage optimization settings.
    |
    */
    'memory' => [
        'max_execution_time' => env('MEMORY_MAX_EXECUTION_TIME', 300),
        'memory_limit' => env('MEMORY_LIMIT', '256M'),
        'chunk_processing_size' => 1000,
        'enable_memory_monitoring' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Optimization
    |--------------------------------------------------------------------------
    |
    | HTTP response optimization settings.
    |
    */
    'response' => [
        // Enable response compression
        'gzip_enabled' => env('RESPONSE_GZIP_ENABLED', true),
        'gzip_level' => 6,
        'gzip_types' => [
            'text/html',
            'text/css',
            'application/javascript',
            'application/json',
            'text/xml',
            'application/xml',
        ],

        // HTTP caching headers
        'cache_headers_enabled' => true,
        'cache_max_age' => 3600,
        'cache_public' => true,

        // ETag support
        'etag_enabled' => true,

        // Minify responses
        'minify_json' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Optimization
    |--------------------------------------------------------------------------
    |
    | Frontend asset optimization settings.
    |
    */
    'frontend' => [
        // Asset minification
        'minify_css' => true,
        'minify_javascript' => true,

        // Asset versioning
        'asset_versioning' => true,

        // Lazy loading
        'lazy_load_images' => true,
        'lazy_load_threshold' => '50px',

        // Image optimization
        'optimize_images' => true,
        'image_formats' => ['webp', 'jpg'],

        // Font optimization
        'font_display' => 'swap', // Prevent blank text during font load

        // Critical CSS
        'critical_css_enabled' => true,

        // Preload important assets
        'preload_critical_assets' => true,

        // Service Worker caching
        'service_worker_enabled' => true,
        'service_worker_cache_version' => 'v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging
    |--------------------------------------------------------------------------
    |
    | Performance monitoring and logging settings.
    |
    */
    'monitoring' => [
        // Enable performance monitoring
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),

        // Performance metrics to track
        'track_request_time' => true,
        'track_memory_usage' => true,
        'track_database_queries' => true,
        'track_cache_hits' => true,

        // Logging
        'log_performance_data' => true,
        'log_path' => 'performance',
        'log_level' => 'debug',

        // Thresholds for alerts
        'alert_thresholds' => [
            'slow_request_ms' => 1000,
            'high_memory_mb' => 128,
            'excessive_queries' => 50,
            'low_cache_hit_ratio' => 0.5,
        ],

        // Performance reporting
        'generate_performance_report' => true,
        'report_frequency' => 'daily', // daily, weekly, monthly
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization Strategies
    |--------------------------------------------------------------------------
    |
    | Strategies for optimizing database queries.
    |
    */
    'strategies' => [
        // Use select() to limit columns
        'selective_columns' => true,

        // Use with() for eager loading
        'eager_loading' => true,

        // Use only() for API resources
        'api_resource_optimization' => true,

        // Use chunking for large datasets
        'chunk_large_datasets' => true,
        'chunk_size' => 1000,

        // Use raw queries only when necessary
        'prefer_eloquent' => true,

        // Use transactions for data consistency
        'use_transactions' => true,

        // Denormalization for read-heavy operations
        'allow_denormalization' => true,

        // Caching frequently accessed data
        'cache_frequently_accessed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Tools
    |--------------------------------------------------------------------------
    |
    | Development-only optimization tools.
    |
    */
    'dev_tools' => [
        // Enable query debugging in development
        'debug_queries' => env('APP_DEBUG', false),

        // Log database operations
        'log_db_operations' => env('APP_DEBUG', false),

        // Show performance bars in development
        'show_performance_bar' => env('APP_DEBUG', false),

        // Enable SQL profiling
        'sql_profiling' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimization Recommendations
    |--------------------------------------------------------------------------
    |
    | Best practices for optimal performance.
    |
    */
    'recommendations' => [
        'use_cache_service' => true,
        'minimize_database_queries' => true,
        'use_eager_loading' => true,
        'use_pagination' => true,
        'use_connection_pooling' => true,
        'monitor_performance' => true,
        'log_slow_queries' => true,
        'optimize_images' => true,
        'minify_assets' => true,
        'use_cdn' => true,
        'enable_compression' => true,
        'set_proper_cache_headers' => true,
        'use_database_indexes' => true,
        'denormalize_when_needed' => true,
        'use_redis_for_caching' => true,
    ],
];
