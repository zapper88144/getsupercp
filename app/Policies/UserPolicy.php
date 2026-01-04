<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Admin can view any user, users can view themselves
        return $user->is_admin || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Super admins can update anyone
        if ($user->role === 'super-admin') {
            return true;
        }

        // Admins can update anyone except super admins
        if ($user->role === 'admin' && $model->role !== 'super-admin') {
            return true;
        }

        // Users can only update themselves
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can delete anyone except other super admins
        if ($user->role === 'super-admin') {
            return $model->role !== 'super-admin';
        }

        // Admins can delete non-admin users
        return $user->role === 'admin' && ! $model->is_admin;
    }

    /**
     * Determine whether the user can suspend the user.
     */
    public function suspend(User $user, User $model): bool
    {
        // Cannot suspend self
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can suspend anyone except other super admins
        if ($user->role === 'super-admin') {
            return $model->role !== 'super-admin';
        }

        // Admins can suspend non-admin users
        return $user->role === 'admin' && ! $model->is_admin;
    }

    /**
     * Determine whether the user can unsuspend the user.
     */
    public function unsuspend(User $user, User $model): bool
    {
        // Super admins can unsuspend anyone except other super admins
        if ($user->role === 'super-admin') {
            return $model->role !== 'super-admin';
        }

        // Admins can unsuspend non-admin users
        return $user->role === 'admin' && ! $model->is_admin;
    }

    /**
     * Determine whether the user can force logout another user.
     */
    public function forceLogout(User $user, User $model): bool
    {
        // Cannot force logout self
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can force logout anyone except other super admins
        if ($user->role === 'super-admin') {
            return $model->role !== 'super-admin';
        }

        // Admins can force logout non-admin users
        return $user->role === 'admin' && ! $model->is_admin;
    }

    /**
     * Determine whether the user can reset two-factor authentication.
     */
    public function resetTwoFactor(User $user, User $model): bool
    {
        // Cannot reset own 2FA
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can reset for anyone except other super admins
        if ($user->role === 'super-admin') {
            return $model->role !== 'super-admin';
        }

        // Admins can reset for non-admin users
        return $user->role === 'admin' && ! $model->is_admin;
    }
}
