<?php

namespace App\Policies;

use App\Models\BackupSchedule;
use App\Models\User;

class BackupSchedulePolicy
{
    public function view(User $user, BackupSchedule $schedule): bool
    {
        return $user->id === $schedule->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BackupSchedule $schedule): bool
    {
        return $user->id === $schedule->user_id;
    }

    public function delete(User $user, BackupSchedule $schedule): bool
    {
        return $user->id === $schedule->user_id;
    }
}
