<?php

namespace App\Policies;

use App\Models\FtpUser;
use App\Models\User;

class FtpUserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FtpUser $ftpUser): bool
    {
        return $user->id === $ftpUser->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FtpUser $ftpUser): bool
    {
        return $user->id === $ftpUser->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FtpUser $ftpUser): bool
    {
        return $user->id === $ftpUser->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FtpUser $ftpUser): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FtpUser $ftpUser): bool
    {
        return false;
    }
}
