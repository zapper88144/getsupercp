<?php

namespace App\Policies;

use App\Models\MonitoringAlert;
use App\Models\User;

class MonitoringAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MonitoringAlert $alert): bool
    {
        return $user->id === $alert->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MonitoringAlert $alert): bool
    {
        return $user->id === $alert->user_id;
    }

    public function delete(User $user, MonitoringAlert $alert): bool
    {
        return $user->id === $alert->user_id;
    }

    public function restore(User $user, MonitoringAlert $alert): bool
    {
        return false;
    }

    public function forceDelete(User $user, MonitoringAlert $alert): bool
    {
        return false;
    }
}
