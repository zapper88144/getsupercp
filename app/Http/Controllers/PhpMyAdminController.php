<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PhpMyAdminController extends Controller
{
    /**
     * Show database manager dashboard
     */
    public function index()
    {
        // Authorize the request - check if user is admin
        if (! Auth::user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        // Check if phpMyAdmin is installed
        $phpMyAdminPath = config('phpmyadmin.path');
        if (! File::isDirectory($phpMyAdminPath)) {
            Log::error('phpMyAdmin not found at configured path', [
                'path' => $phpMyAdminPath,
                'user_id' => Auth::id(),
            ]);
            abort(404, 'phpMyAdmin installation not found');
        }

        Log::info('Database manager dashboard accessed', [
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email,
            'ip' => request()->ip(),
        ]);

        // Get database statistics (handle both MySQL and SQLite)
        $databaseInfo = [];
        $status = [];

        try {
            $driver = config('database.default');

            if ($driver === 'sqlite') {
                // For SQLite, get the database file information
                $dbPath = config('database.connections.sqlite.database');
                if ($dbPath && file_exists($dbPath)) {
                    $size = filesize($dbPath) / (1024 * 1024); // Convert to MB

                    // Get table count
                    $tables = DB::select("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    $tableCount = $tables[0]->count ?? 0;

                    // Get row count from main tables
                    $allTables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    $totalRows = 0;

                    foreach ($allTables as $table) {
                        try {
                            $rowCountResult = DB::selectOne("SELECT COUNT(*) as count FROM {$table->name}");
                            $totalRows += $rowCountResult->count ?? 0;
                        } catch (\Exception $e) {
                            // Skip tables that can't be counted
                        }
                    }

                    $databaseInfo[] = [
                        'name' => basename($dbPath),
                        'tables' => $tableCount,
                        'size_mb' => round($size, 2),
                        'rows' => $totalRows,
                    ];
                }

                $status = [
                    'driver' => 'SQLite',
                    'version' => DB::selectOne('SELECT sqlite_version() as version')?->version ?? 'Unknown',
                ];
            } else {
                // For MySQL/PostgreSQL
                $databases = DB::select('SHOW DATABASES');

                $databaseInfo = array_map(function ($db) {
                    $dbName = (array) $db;
                    $name = reset($dbName);

                    try {
                        $tables = DB::select('SELECT TABLE_NAME, ENGINE, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?', [$name]);
                        $size = DB::selectOne('
                            SELECT 
                                SUM(ROUND(((data_length + index_length) / 1024 / 1024), 2)) as size_mb
                            FROM information_schema.TABLES
                            WHERE table_schema = ?
                        ', [$name]);

                        $sizeMb = $size && $size->size_mb !== null ? (float) $size->size_mb : 0;
                        $rowCount = array_sum(array_column($tables, 'TABLE_ROWS') ?? []);

                        return [
                            'name' => $name,
                            'tables' => (int) count($tables),
                            'size_mb' => round($sizeMb, 2),
                            'rows' => (int) $rowCount,
                        ];
                    } catch (\Exception $e) {
                        return [
                            'name' => $name,
                            'tables' => 0,
                            'size_mb' => 0,
                            'rows' => 0,
                        ];
                    }
                }, $databases);

                $status = DB::selectOne('SHOW STATUS');
                $status = $status ? (array) $status : [];
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch database info', ['error' => $e->getMessage()]);
            $databaseInfo = [];
            $status = [];
        }

        return Inertia::render('Admin/DatabaseManager', [
            'databases' => $databaseInfo,
            'stats' => $status,
        ]);
    }

    /**
     * Get database status and statistics
     */
    public function status()
    {
        if (! Auth::user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        try {
            $driver = config('database.default');

            if ($driver === 'sqlite') {
                // SQLite status information
                $dbPath = config('database.connections.sqlite.database');
                $size = $dbPath && file_exists($dbPath) ? filesize($dbPath) / (1024 * 1024) : 0;
                $tables = DB::select("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $version = DB::selectOne('SELECT sqlite_version() as version');

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'driver' => 'SQLite',
                        'version' => $version?->version ?? 'Unknown',
                        'database_size_mb' => round($size, 2),
                        'table_count' => $tables[0]->count ?? 0,
                        'timestamp' => now(),
                    ],
                ]);
            } else {
                // MySQL/PostgreSQL status
                $databases = DB::select('SHOW DATABASES');
                $variables = DB::select('SHOW VARIABLES LIKE "%size%"');
                $tables = DB::select('SELECT COUNT(*) as count FROM information_schema.tables');

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'databases' => $databases,
                        'variables' => $variables,
                        'tables' => $tables,
                        'timestamp' => now(),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of databases
     */
    public function getDatabases()
    {
        if (! Auth::user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        try {
            $driver = config('database.default');

            if ($driver === 'sqlite') {
                // For SQLite, return the main database file
                $dbPath = config('database.connections.sqlite.database');
                $databases = [];

                if ($dbPath && file_exists($dbPath)) {
                    $databases[] = [
                        'Database' => basename($dbPath),
                        'Path' => $dbPath,
                        'Size_MB' => round(filesize($dbPath) / (1024 * 1024), 2),
                    ];
                }

                return response()->json([
                    'status' => 'success',
                    'driver' => 'sqlite',
                    'databases' => $databases,
                ]);
            } else {
                // MySQL/PostgreSQL
                $databases = DB::select('SHOW DATABASES');

                return response()->json([
                    'status' => 'success',
                    'driver' => 'mysql',
                    'databases' => $databases,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get database details
     */
    public function getDatabase($name)
    {
        if (! Auth::user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        try {
            $driver = config('database.default');

            if ($driver === 'sqlite') {
                // SQLite database details
                $dbPath = config('database.connections.sqlite.database');
                if (! $dbPath || basename($dbPath) !== $name) {
                    throw new \Exception('Database not found');
                }

                // Get table information
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
                $size = file_exists($dbPath) ? filesize($dbPath) / (1024 * 1024) : 0;

                $tableData = [];
                foreach ($tables as $table) {
                    $rowCount = DB::selectOne("SELECT COUNT(*) as count FROM {$table->name}");
                    $tableData[] = [
                        'TABLE_NAME' => $table->name,
                        'TABLE_ROWS' => $rowCount->count ?? 0,
                    ];
                }

                return response()->json([
                    'status' => 'success',
                    'name' => $name,
                    'tables' => $tableData,
                    'size' => round($size, 2),
                ]);
            } else {
                // MySQL/PostgreSQL database details
                // Prevent SQL injection
                if (! preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                    throw new \Exception('Invalid database name');
                }

                $tables = DB::select('SELECT * FROM information_schema.tables WHERE table_schema = ?', [$name]);
                $size = DB::selectOne('
                    SELECT 
                        SUM(ROUND(((data_length + index_length) / 1024 / 1024), 2)) as size_mb
                    FROM information_schema.tables
                    WHERE table_schema = ?
                ', [$name]);

                return response()->json([
                    'status' => 'success',
                    'name' => $name,
                    'tables' => $tables,
                    'size' => $size->size_mb ?? 0,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute a query (with restrictions)
     */
    public function executeQuery()
    {
        if (! Auth::user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        try {
            $query = request()->input('query');

            // Prevent dangerous operations
            $dangerous = ['DROP', 'TRUNCATE', 'DELETE', 'ALTER'];
            foreach ($dangerous as $keyword) {
                if (stripos($query, $keyword) !== false) {
                    throw new \Exception("Query contains restricted keyword: $keyword");
                }
            }

            // Only allow SELECT queries by default
            if (! preg_match('/^SELECT/i', trim($query))) {
                throw new \Exception('Only SELECT queries are allowed');
            }

            $results = DB::select(DB::raw($query));

            return response()->json([
                'status' => 'success',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check phpMyAdmin installation
     */
    public function check()
    {
        if (! Auth::user()->is_admin) {
            abort(403, 'This action is unauthorized.');
        }

        $phpMyAdminPath = config('phpmyadmin.path');
        $configFile = $phpMyAdminPath.'/config.inc.php';

        $status = [
            'enabled' => config('phpmyadmin.enabled'),
            'installed' => File::isDirectory($phpMyAdminPath),
            'config_exists' => File::exists($configFile),
            'config_readable' => File::exists($configFile) && is_readable($configFile),
            'path' => $phpMyAdminPath,
            'url' => config('phpmyadmin.url'),
        ];

        return response()->json($status);
    }
}
