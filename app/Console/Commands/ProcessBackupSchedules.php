<?php

namespace App\Console\Commands;

use App\Models\BackupSchedule;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessBackupSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:process-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled backups';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $schedules = BackupSchedule::where('is_enabled', true)
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No schedules to process.');

            return 0;
        }

        $this->info("Processing {$schedules->count()} backup schedules...");

        foreach ($schedules as $schedule) {
            $this->info("Processing schedule: {$schedule->name} (ID: {$schedule->id})");

            try {
                $startTime = now();

                // Determine targets
                $targets = $schedule->targets ?? [];

                // For now, we'll handle full, database_only, and files_only
                // This is a simplified implementation for the task
                if ($schedule->backup_type === 'database_only') {
                    $databases = $targets['databases'] ?? [];
                    foreach ($databases as $dbName) {
                        $backupService->createBackup($schedule->user, 'database', $dbName);
                    }
                } elseif ($schedule->backup_type === 'files_only') {
                    $domains = $targets['web_domains'] ?? [];
                    foreach ($domains as $domainName) {
                        $backupService->createBackup($schedule->user, 'web', $domainName);
                    }
                } else {
                    // Full backup - backup all user's domains and databases
                    $domains = $schedule->user->webDomains;
                    foreach ($domains as $domain) {
                        $backupService->createBackup($schedule->user, 'web', $domain->domain);
                    }

                    $databases = $schedule->user->databases;
                    foreach ($databases as $database) {
                        $backupService->createBackup($schedule->user, 'database', $database->name);
                    }
                }

                $duration = now()->diffInSeconds($startTime);

                $schedule->update([
                    'last_run_at' => now(),
                    'last_run_duration_seconds' => $duration,
                    'next_run_at' => $this->calculateNextRunAt($schedule),
                    'run_count' => $schedule->run_count + 1,
                ]);

                $this->info("Successfully processed schedule: {$schedule->name}");

                // Cleanup old backups based on retention
                $this->cleanupOldBackups($schedule, $backupService);
            } catch (\Exception $e) {
                Log::error("Failed to process backup schedule {$schedule->id}: ".$e->getMessage());

                $schedule->update([
                    'failed_count' => $schedule->failed_count + 1,
                    'next_run_at' => $this->calculateNextRunAt($schedule),
                ]);

                $this->error("Failed to process schedule: {$schedule->name}");
            }
        }

        return 0;
    }

    protected function cleanupOldBackups(BackupSchedule $schedule, BackupService $backupService): void
    {
        $retentionDays = $schedule->retention_days ?? 30;
        $cutoffDate = now()->subDays($retentionDays);

        $oldBackups = \App\Models\Backup::where('user_id', $schedule->user_id)
            ->where('created_at', '<', $cutoffDate)
            ->where('status', 'completed')
            ->get();

        if ($oldBackups->isNotEmpty()) {
            $this->info("Cleaning up {$oldBackups->count()} old backups for schedule: {$schedule->name}");
            foreach ($oldBackups as $backup) {
                try {
                    $backupService->delete($backup);
                } catch (\Exception $e) {
                    Log::error("Failed to delete old backup {$backup->id}: ".$e->getMessage());
                }
            }
        }
    }

    protected function calculateNextRunAt(BackupSchedule $schedule): \Illuminate\Support\Carbon
    {
        $time = $schedule->time ?? '02:00';
        [$hour, $minute] = explode(':', $time);

        $next = now()->setHour((int) $hour)->setMinute((int) $minute)->setSecond(0);

        if ($next->isPast()) {
            if ($schedule->frequency === 'daily') {
                $next = $next->addDay();
            } elseif ($schedule->frequency === 'weekly') {
                $next = $next->addWeek();
            } elseif ($schedule->frequency === 'monthly') {
                $next = $next->addMonth();
            } else {
                $next = $next->addDay();
            }
        }

        return $next;
    }
}
