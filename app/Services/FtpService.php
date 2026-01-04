<?php

namespace App\Services;

use App\Models\FtpUser;
use App\Models\User;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FtpService
{
    use HandlesDaemonErrors;

    public function __construct(
        private RustDaemonClient $daemon,
        private SystemSyncService $syncService
    ) {}

    /**
     * Create an FTP user
     */
    public function create(User $user, array $data): FtpUser
    {
        // Validate username
        if (! $this->isValidUsername($data['username'])) {
            throw new Exception('Invalid FTP username format');
        }

        // Check if user already exists
        if (FtpUser::where('username', $data['username'])->exists()) {
            throw new Exception('FTP user already exists');
        }

        // Validate home directory
        $homeDir = $data['home_dir'] ?? "/home/ftp/{$data['username']}";
        if (! $this->isValidPath($homeDir)) {
            throw new Exception('Invalid home directory path');
        }

        return $this->handleDaemonCall(function () use ($user, $data, $homeDir) {
            $password = $data['password'] ?? Str::random(16);

            // Create FTP user on daemon
            $this->daemon->createFtpUser(
                $data['username'],
                $password,
                $homeDir
            );

            Log::info('FTP user created on daemon', [
                'username' => $data['username'],
            ]);

            // Create database record
            $ftpUser = FtpUser::create([
                'user_id' => $user->id,
                'username' => $data['username'],
                'password' => bcrypt($password),
                'home_dir' => $homeDir,
                'status' => 'active',
            ]);

            // Sync to Pure-FTPd system tables
            $this->syncService->syncFtpUser($ftpUser);

            return $ftpUser;
        }, "Failed to create FTP user: {$data['username']}");
    }

    /**
     * Update an FTP user
     */
    public function update(FtpUser $ftpUser, array $data): FtpUser
    {
        return $this->handleDaemonCall(function () use ($ftpUser, $data) {
            // If password is being updated
            if (isset($data['password'])) {
                $password = $data['password'];
                $this->daemon->createFtpUser(
                    $ftpUser->username,
                    $password,
                    $ftpUser->home_dir
                );
                $data['password'] = bcrypt($password);
            }

            // Update status if provided
            if (isset($data['status'])) {
                $ftpUser->update(['status' => $data['status']]);
            }

            $ftpUser->update($data);

            // Sync to Pure-FTPd system tables
            $this->syncService->syncFtpUser($ftpUser);

            Log::info('FTP user updated', [
                'username' => $ftpUser->username,
            ]);

            return $ftpUser->fresh();
        }, "Failed to update FTP user: {$ftpUser->username}");
    }

    /**
     * Delete an FTP user
     */
    public function delete(FtpUser $ftpUser): bool
    {
        return $this->handleDaemonCall(function () use ($ftpUser) {
            $username = $ftpUser->username;

            // Delete from daemon
            $this->daemon->deleteFtpUser($username);

            Log::info('FTP user deleted from daemon', [
                'username' => $username,
            ]);

            // Delete from Pure-FTPd system tables
            $this->syncService->deleteFtpUser($username);

            // Delete from database
            return $ftpUser->delete();
        }, "Failed to delete FTP user: {$ftpUser->username}");
    }

    /**
     * List FTP users
     */
    public function list(): array
    {
        try {
            return $this->daemon->listFtpUsers();
        } catch (Exception $e) {
            Log::error('Failed to list FTP users', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Enable FTP user
     */
    public function enable(FtpUser $ftpUser): FtpUser
    {
        try {
            $ftpUser->update(['status' => 'active']);
            Log::info('FTP user enabled', ['username' => $ftpUser->username]);

            return $ftpUser->fresh();
        } catch (Exception $e) {
            Log::error('Failed to enable FTP user', [
                'username' => $ftpUser->username,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to enable FTP user: {$e->getMessage()}");
        }
    }

    /**
     * Disable FTP user
     */
    public function disable(FtpUser $ftpUser): FtpUser
    {
        try {
            $ftpUser->update(['status' => 'suspended']);
            Log::info('FTP user disabled', ['username' => $ftpUser->username]);

            return $ftpUser->fresh();
        } catch (Exception $e) {
            Log::error('Failed to disable FTP user', [
                'username' => $ftpUser->username,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to disable FTP user: {$e->getMessage()}");
        }
    }

    /**
     * Validate FTP username format
     */
    private function isValidUsername(string $username): bool
    {
        // FTP usernames: 1-32 chars, alphanumeric, underscore, dash
        return (bool) preg_match('/^[a-zA-Z0-9_-]{1,32}$/', $username);
    }

    /**
     * Validate home directory path
     */
    private function isValidPath(string $path): bool
    {
        // Ensure path is absolute and doesn't contain dangerous patterns
        return (bool) preg_match('/^\/[a-zA-Z0-9\/_\-\.]+$/', $path);
    }

    /**
     * Check if daemon is running
     */
    public function isDaemonRunning(): bool
    {
        return $this->daemon->isRunning();
    }
}
