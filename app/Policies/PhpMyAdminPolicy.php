<?php

namespace App\Policies;

use App\Models\User;

class PhpMyAdminPolicy
{
    /**
     * Determine if the user can access phpMyAdmin
     */
    public function isAdmin(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine if the user can view database status
     */
    public function viewStatus(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine if the user can manage databases
     */
    public function manageDatabases(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine if the user can execute queries
     */
    public function executeQueries(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine if the user can backup databases
     */
    public function backupDatabases(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine if the user can restore databases
     */
    public function restoreDatabases(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
