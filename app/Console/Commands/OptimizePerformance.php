<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizePerformance extends Command
{
    protected $signature = 'optimize:performance {--analyze} {--clear-cache} {--check-indexes}';

    protected $description = 'Analyze and optimize application performance';

    public function handle(): int
    {
        $this->line('');
        $this->info('🚀 Performance Optimization Tool');
        $this->line('');

        if ($this->option('analyze')) {
            $this->analyzePerformance();
        } elseif ($this->option('clear-cache')) {
            $this->clearAllCaches();
        } elseif ($this->option('check-indexes')) {
            $this->checkDatabaseIndexes();
        } else {
            $this->showPerformanceMenu();
        }

        return Command::SUCCESS;
    }

    protected function showPerformanceMenu(): void
    {
        $this->info('Select an optimization task:');
        $this->line('');
        $this->line('1. Analyze Performance Metrics');
        $this->line('2. Clear All Caches');
        $this->line('3. Check Database Indexes');
        $this->line('4. View Cache Configuration');
        $this->line('5. View Optimization Settings');
        $this->line('');

        $choice = $this->choice('What would you like to do?', [
            'Analyze Performance Metrics',
            'Clear All Caches',
            'Check Database Indexes',
            'View Cache Configuration',
            'View Optimization Settings',
        ]);

        match ($choice) {
            'Analyze Performance Metrics' => $this->analyzePerformance(),
            'Clear All Caches' => $this->clearAllCaches(),
            'Check Database Indexes' => $this->checkDatabaseIndexes(),
            'View Cache Configuration' => $this->viewCacheConfiguration(),
            'View Optimization Settings' => $this->viewOptimizationSettings(),
        };
    }

    protected function analyzePerformance(): void
    {
        $this->info('📊 Performance Analysis');
        $this->line('');

        // Cache analysis
        $this->line('Cache Configuration:');
        $this->line('  Driver: '.config('cache.default'));
        $this->line('  Enabled: '.(config('optimization.caching.enabled') ? 'Yes' : 'No'));
        $this->line('  Default TTL: '.config('optimization.caching.default_ttl').' seconds');
        $this->line('');

        // Database analysis
        $this->line('Database Configuration:');
        $this->line('  Connection: '.config('database.default'));
        $this->line('  Slow Query Threshold: '.config('optimization.database.slow_query_threshold_ms').'ms');
        $this->line('  Log Slow Queries: '.(config('optimization.database.log_slow_queries') ? 'Yes' : 'No'));
        $this->line('');

        // Performance monitoring
        $this->line('Monitoring:');
        $this->line('  Enabled: '.(config('optimization.monitoring.enabled') ? 'Yes' : 'No'));
        $this->line('  Log Performance: '.(config('optimization.monitoring.log_performance_data') ? 'Yes' : 'No'));
        $this->line('');

        // Check recent logs
        $this->checkRecentPerformanceLogs();

        // Get performance recommendations
        $this->getRecommendations();
    }

    protected function checkRecentPerformanceLogs(): void
    {
        $logPath = storage_path('logs/performance.log');

        if (! file_exists($logPath)) {
            $this->line('No performance logs found yet.');

            return;
        }

        $this->line('Recent Performance Logs:');
        $lines = array_slice(file($logPath, FILE_IGNORE_NEW_LINES), -5);
        foreach ($lines as $line) {
            $this->line('  '.substr($line, 0, 100));
        }
        $this->line('');
    }

    protected function getRecommendations(): void
    {
        $this->line('Recommendations:');
        $recommendations = [];

        // Check cache driver
        if (config('cache.default') === 'file') {
            $recommendations[] = '⚠️  Consider using Redis instead of file cache for better performance';
        }

        // Check if caching is enabled
        if (! config('optimization.caching.enabled')) {
            $recommendations[] = '⚠️  Caching is disabled. Enable it in config/optimization.php';
        }

        // Check if monitoring is enabled
        if (! config('optimization.monitoring.enabled')) {
            $recommendations[] = '💡 Enable monitoring to track performance metrics';
        }

        // Check database settings
        if (! config('optimization.database.log_slow_queries')) {
            $recommendations[] = '💡 Enable slow query logging to identify bottlenecks';
        }

        if (empty($recommendations)) {
            $this->line('✅ All recommended optimizations are enabled');
        } else {
            foreach ($recommendations as $rec) {
                $this->line('  '.$rec);
            }
        }
        $this->line('');
    }

    protected function clearAllCaches(): void
    {
        $this->info('🗑️  Clearing All Caches');
        $this->line('');

        try {
            Cache::flush();
            $this->info('✅ All caches cleared successfully');
            Log::info('All caches cleared by command');
        } catch (\Exception $e) {
            $this->error('❌ Failed to clear caches: '.$e->getMessage());
        }

        $this->line('');
    }

    protected function checkDatabaseIndexes(): void
    {
        $this->info('🔍 Checking Database Indexes');
        $this->line('');

        $driver = config('database.default');

        if ($driver !== 'mysql') {
            $this->warn('Index checking currently only supports MySQL');

            return;
        }

        $this->checkMySQLIndexes();
    }

    protected function checkMySQLIndexes(): void
    {
        $database = config('database.connections.mysql.database');

        try {
            $tables = DB::select('
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = ?
            ', [$database]);

            $this->line('Database: '.$database);
            $this->line('');

            $criticalTables = [
                'users', 'web_domains', 'email_accounts', 'ssl_certificates',
                'databases', 'backups', 'audit_logs', 'monitoring_alerts',
            ];

            foreach ($criticalTables as $tableName) {
                $exists = in_array($tableName, array_column($tables, 'TABLE_NAME'));

                if (! $exists) {
                    continue;
                }

                $indexes = DB::select('
                    SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                    ORDER BY SEQ_IN_INDEX
                ', [$database, $tableName]);

                $this->line("Table: {$tableName}");
                if (empty($indexes)) {
                    $this->line('  ⚠️  No indexes found');
                } else {
                    $currentIndex = null;
                    foreach ($indexes as $index) {
                        if ($index->INDEX_NAME !== $currentIndex) {
                            $this->line("  ✅ {$index->INDEX_NAME}: {$index->COLUMN_NAME}");
                            $currentIndex = $index->INDEX_NAME;
                        } else {
                            $this->line("      , {$index->COLUMN_NAME}");
                        }
                    }
                }
                $this->line('');
            }
        } catch (\Exception $e) {
            $this->error('Error checking indexes: '.$e->getMessage());
        }
    }

    protected function viewCacheConfiguration(): void
    {
        $this->info('⚙️  Cache Configuration');
        $this->line('');

        $config = config('optimization.caching');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', $config['enabled'] ? 'Yes' : 'No'],
                ['Driver', $config['driver']],
                ['Default TTL', $config['default_ttl'].' seconds'],
            ]
        );

        $this->line('');
        $this->line('Data Type TTLs:');
        foreach ($config['ttls'] as $type => $ttl) {
            $this->line("  {$type}: {$ttl} minutes");
        }
        $this->line('');
    }

    protected function viewOptimizationSettings(): void
    {
        $this->info('⚙️  Optimization Settings');
        $this->line('');

        $settings = [
            'Caching Enabled' => config('optimization.caching.enabled'),
            'Monitoring Enabled' => config('optimization.monitoring.enabled'),
            'Log Slow Queries' => config('optimization.database.log_slow_queries'),
            'Query Optimization' => config('optimization.database.optimize_with_indexes'),
            'Eager Loading' => config('optimization.database.eager_load_relations'),
            'Prevent N+1 Queries' => config('optimization.database.prevent_n_plus_one'),
            'Gzip Compression' => config('optimization.response.gzip_enabled'),
        ];

        foreach ($settings as $setting => $value) {
            $status = $value ? '✅' : '❌';
            $this->line("{$status} {$setting}");
        }
        $this->line('');
    }
}
