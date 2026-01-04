<?php

namespace App\Services;

use App\Models\CronJob;
use App\Models\User;
use App\Traits\HandlesDaemonErrors;

class CronService
{
    use HandlesDaemonErrors;

    private RustDaemonClient $daemon;

    public function __construct(?RustDaemonClient $daemon = null)
    {
        $this->daemon = $daemon ?? new RustDaemonClient;
    }

    /**
     * Sync cron jobs for a user with the system
     */
    public function syncForUser(User $user): string
    {
        $jobs = $user->cronJobs()
            ->where('is_active', true)
            ->get(['command', 'schedule'])
            ->toArray();

        return $this->handleDaemonCall(function () use ($user, $jobs) {
            return $this->daemon->updateCronJobs($user->name, $jobs);
        }, "Failed to sync cron jobs for user {$user->name}");
    }

    /**
     * Create a new cron job
     */
    public function create(User $user, array $data): CronJob
    {
        return $this->handleDaemonCall(function () use ($user, $data) {
            $cronJob = $user->cronJobs()->create([
                'command' => $data['command'],
                'schedule' => $data['schedule'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]);

            $this->syncForUser($user);

            return $cronJob;
        }, 'Failed to create cron job');
    }

    /**
     * Update a cron job
     */
    public function update(CronJob $cronJob, array $data): CronJob
    {
        return $this->handleDaemonCall(function () use ($cronJob, $data) {
            $cronJob->update($data);
            $this->syncForUser($cronJob->user);

            return $cronJob->fresh();
        }, 'Failed to update cron job');
    }

    /**
     * Delete a cron job
     */
    public function delete(CronJob $cronJob): bool
    {
        return $this->handleDaemonCall(function () use ($cronJob) {
            $user = $cronJob->user;
            $cronJob->delete();
            $this->syncForUser($user);

            return true;
        }, 'Failed to delete cron job');
    }
}
