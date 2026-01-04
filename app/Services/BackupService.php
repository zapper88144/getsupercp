<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Models\User;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;

class BackupService
{
    use HandlesDaemonErrors;

    private RustDaemonClient $daemon;

    public function __construct(?RustDaemonClient $daemon = null)
    {
        $this->daemon = $daemon ?? new RustDaemonClient;
    }

    /**
     * Create a manual backup
     */
    public function createBackup(User $user, string $type, string $source): Backup
    {
        // Validate backup type
        $validTypes = ['web', 'database'];
        if (! in_array($type, $validTypes)) {
            throw new Exception("Invalid backup type: {$type}");
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $name = "backup_{$type}_{$source}_{$timestamp}";
        $path = "/var/lib/supercp/backups/{$name}.tar.gz"; // Default system path

        $backup = Backup::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
            'source' => $source,
            'path' => $path,
            'status' => 'pending',
        ]);

        return $this->handleDaemonCall(function () use ($backup, $type, $source, $name) {
            if ($type === 'web') {
                $domain = \App\Models\WebDomain::where('domain', $source)->first();
                $sourcePath = $domain ? $domain->root_path : "/home/super/web/{$source}/public";

                $response = $this->daemon->createBackup($name, $sourcePath);
            } else {
                // For database, source is the database name
                $response = $this->daemon->createDbBackup($source);
            }

            // The daemon returns the path to the created backup
            if ($response) {
                $actualPath = $response;
                $size = 0;
                if (file_exists($actualPath)) {
                    $size = filesize($actualPath);
                }

                $backup->update([
                    'status' => 'completed',
                    'size' => $size,
                    'path' => $actualPath,
                ]);
            } else {
                $backup->update(['status' => 'failed']);
                throw new Exception('Daemon returned empty response for backup creation');
            }

            return $backup->fresh();
        }, 'Failed to create backup', function () use ($backup) {
            $backup->update(['status' => 'failed']);
        });
    }

    /**
     * Restore from a backup
     */
    public function restore(Backup $backup): bool
    {
        return $this->handleDaemonCall(function () use ($backup) {
            if ($backup->type === 'web') {
                $domain = \App\Models\WebDomain::where('domain', $backup->source)->first();
                $targetPath = $domain ? $domain->root_path : "/home/super/web/{$backup->source}/public";

                $this->daemon->restoreBackup($backup->path, $targetPath);
            } else {
                $this->daemon->restoreDbBackup($backup->path, $backup->source);
            }

            return true;
        }, 'Failed to restore backup');
    }

    /**
     * Delete a backup
     */
    public function delete(Backup $backup): bool
    {
        return $this->handleDaemonCall(function () use ($backup) {
            // Delete file from daemon
            if ($backup->path) {
                $this->daemon->deleteFile($backup->path);
            }

            // Delete record
            return $backup->delete();
        }, 'Failed to delete backup');
    }

    /**
     * Create a backup schedule
     */
    public function createSchedule(User $user, array $data): BackupSchedule
    {
        // Validate frequency
        $validFrequencies = ['daily', 'weekly', 'monthly', 'custom'];
        if (! in_array($data['frequency'], $validFrequencies)) {
            throw new Exception("Invalid frequency: {$data['frequency']}");
        }

        try {
            return BackupSchedule::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'frequency' => $data['frequency'],
                'time' => $data['time'] ?? '02:00',
                'day_of_week' => $data['day_of_week'] ?? null,
                'day_of_month' => $data['day_of_month'] ?? null,
                'backup_type' => $data['backup_type'] ?? 'full',
                'retention_days' => $data['retention_days'] ?? 30,
                'compress' => $data['compress'] ?? true,
                'encrypt' => $data['encrypt'] ?? false,
                'encryption_key' => $data['encryption_key'] ?? null,
                'notify_on_completion' => $data['notify_on_completion'] ?? true,
                'notify_on_failure' => $data['notify_on_failure'] ?? true,
                'is_enabled' => $data['is_enabled'] ?? true,
                'targets' => $data['targets'] ?? null,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create backup schedule', [
                'name' => $data['name'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to create backup schedule: {$e->getMessage()}");
        }
    }

    /**
     * Update a backup schedule
     */
    public function updateSchedule(BackupSchedule $schedule, array $data): BackupSchedule
    {
        try {
            $schedule->update($data);

            Log::info('Backup schedule updated', [
                'schedule_id' => $schedule->id,
                'name' => $schedule->name,
            ]);

            return $schedule->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update backup schedule', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to update backup schedule: {$e->getMessage()}");
        }
    }

    /**
     * Delete a backup schedule
     */
    public function deleteSchedule(BackupSchedule $schedule): bool
    {
        try {
            Log::info('Backup schedule deleted', [
                'schedule_id' => $schedule->id,
                'name' => $schedule->name,
            ]);

            return $schedule->delete();
        } catch (Exception $e) {
            Log::error('Failed to delete backup schedule', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to delete backup schedule: {$e->getMessage()}");
        }
    }

    /**
     * Check if daemon is running
     */
    public function isDaemonRunning(): bool
    {
        return $this->daemon->isRunning();
    }
}
