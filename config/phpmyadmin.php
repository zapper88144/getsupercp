<?php

/**
 * phpMyAdmin Configuration for GetSuperCP
 */

return [
    // Enable or disable phpMyAdmin integration
    'enabled' => env('PHPMYADMIN_ENABLED', false),

    // Path to phpMyAdmin installation
    'path' => env('PHPMYADMIN_PATH', '/home/super/phpmyadmin'),

    // URL path for phpMyAdmin access
    'url' => env('PHPMYADMIN_URL', '/phpmyadmin'),

    // Allowed IP addresses (comma-separated)
    'allowed_ips' => explode(',', env('PHPMYADMIN_ALLOWED_IPS', '127.0.0.1,::1')),

    // Database configuration
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],

    // Security settings
    'security' => [
        // Require admin role
        'require_admin' => true,

        // Enable authentication logging
        'log_access' => true,

        // Session timeout in minutes
        'session_timeout' => 60,

        // Allow only HTTPS (in production)
        'force_https' => env('APP_ENV') === 'production',

        // Disable dangerous features
        'disable_file_operations' => env('APP_ENV') === 'production',
    ],

    // Feature toggles
    'features' => [
        // Allow database creation
        'allow_create_database' => true,

        // Allow user creation
        'allow_create_user' => true,

        // Allow database export
        'allow_export' => true,

        // Allow database import
        'allow_import' => true,

        // Show table structure
        'show_structure' => true,

        // Show table data
        'show_data' => true,
    ],

    // Export options
    'export' => [
        // Default format: sql, csv, json, xml, etc.
        'default_format' => 'sql',

        // Allowed formats
        'allowed_formats' => ['sql', 'csv', 'json', 'xml', 'xlsx'],

        // Maximum export size in MB
        'max_size' => 100,
    ],

    // Import options
    'import' => [
        // Maximum upload size in MB
        'max_size' => 100,

        // Allowed file types
        'allowed_types' => ['sql', 'csv', 'json', 'xml'],

        // Auto-detect charset
        'auto_detect_charset' => true,
    ],

    // Appearance
    'appearance' => [
        // Default theme
        'theme' => env('PHPMYADMIN_THEME', 'pmahomme'),

        // Items per page
        'items_per_page' => 25,

        // Default tab
        'default_tab' => 'structure',

        // Show database info
        'show_info' => true,
    ],

    // Performance
    'performance' => [
        // Enable query caching
        'cache_queries' => true,

        // Cache TTL in minutes
        'cache_ttl' => 60,

        // Maximum number of rows to display without confirmation
        'max_rows_display' => 1000,

        // Enable slow query logging
        'log_slow_queries' => env('DB_LOG_SLOW_QUERIES', true),

        // Slow query threshold in milliseconds
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD_MS', 1000),
    ],

    // Logging
    'logging' => [
        // Log all queries
        'log_queries' => env('PHPMYADMIN_LOG_QUERIES', false),

        // Log file location
        'log_file' => storage_path('logs/phpmyadmin.log'),

        // Log level: debug, info, warning, error
        'log_level' => env('LOG_LEVEL', 'info'),

        // Keep logs for X days
        'retention_days' => 30,
    ],

    // Backup configuration
    'backup' => [
        // Auto backup databases
        'auto_backup' => true,

        // Backup schedule (cron expression)
        'schedule' => '0 2 * * *', // Daily at 2 AM

        // Backup directory
        'directory' => storage_path('backups/database'),

        // Retention days
        'retention_days' => 30,
    ],
];
