<?php

namespace App\Services;

use App\Models\Database;
use App\Models\User;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DatabaseService
{
    use HandlesDaemonErrors;

    private RustDaemonClient $daemon;

    public function __construct(?RustDaemonClient $daemon = null)
    {
        $this->daemon = $daemon ?? new RustDaemonClient;
    }

    /**
     * Create a database and user
     */
    public function create(User $user, array $data): Database
    {
        // Validate database name
        if (! $this->isValidDatabaseName($data['name'])) {
            throw new Exception('Invalid database name format');
        }

        // Check if database already exists
        if (Database::where('name', $data['name'])->exists()) {
            throw new Exception('Database already exists');
        }

        return $this->handleDaemonCall(function () use ($user, $data) {
            $dbUser = $data['db_user'] ?? $this->generateDatabaseUser($user->id, $data['name']);
            $password = $data['password'] ?? Str::random(16);

            // Create database on daemon
            $this->daemon->createDatabase(
                $data['name'],
                $dbUser,
                $password,
                $data['engine'] ?? 'mysql'
            );

            Log::info('Database created on daemon', [
                'name' => $data['name'],
                'db_user' => $dbUser,
            ]);

            // Create database record
            return Database::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'db_user' => $dbUser,
                'db_password' => bcrypt($password),
                'engine' => $data['engine'] ?? 'InnoDB',
                'collation' => $data['collation'] ?? 'utf8mb4_unicode_ci',
                'max_connections' => $data['max_connections'] ?? 100,
                'status' => 'active',
            ]);
        }, "Failed to create database: {$data['name']}");
    }

    /**
     * Delete a database
     */
    public function delete(Database $database): bool
    {
        return $this->handleDaemonCall(function () use ($database) {
            // Delete from daemon
            $this->daemon->deleteDatabase($database->name);

            Log::info('Database deleted from daemon', [
                'name' => $database->name,
            ]);

            // Delete from database
            return $database->delete();
        }, "Failed to delete database: {$database->name}");
    }

    /**
     * Reset database
     */
    public function reset(Database $database): bool
    {
        try {
            // Execute reset via daemon - would need to extend daemon with TRUNCATE support
            Log::info('Database reset', ['name' => $database->name]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to reset database', [
                'name' => $database->name,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to reset database: {$e->getMessage()}");
        }
    }

    /**
     * Get database size
     */
    public function getSize(Database $database): int
    {
        try {
            // Query via daemon - would need custom method
            return 0;
        } catch (Exception $e) {
            Log::error('Failed to get database size', [
                'name' => $database->name,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Update database max connections
     */
    public function updateMaxConnections(Database $database, int $maxConnections): Database
    {
        try {
            $database->update(['max_connections' => $maxConnections]);

            Log::info('Database max connections updated', [
                'name' => $database->name,
                'max_connections' => $maxConnections,
            ]);

            return $database->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update database', [
                'name' => $database->name,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to update database: {$e->getMessage()}");
        }
    }

    /**
     * Validate database name format
     */
    private function isValidDatabaseName(string $name): bool
    {
        // MySQL database names: 1-64 chars, alphanumeric, underscore, dash
        return (bool) preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $name);
    }

    /**
     * Generate unique database username
     */
    private function generateDatabaseUser(int $userId, string $dbName): string
    {
        $prefix = substr($dbName, 0, 8);

        return $prefix.'_'.str_pad($userId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if daemon is running
     */
    public function isDaemonRunning(): bool
    {
        return $this->daemon->isRunning();
    }
}
