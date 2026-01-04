<?php

namespace App\Policies;

use App\Models\FirewallRule;
use App\Models\User;

class FirewallRulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FirewallRule $rule): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FirewallRule $rule): bool
    {
        return true;
    }

    public function delete(User $user, FirewallRule $rule): bool
    {
        return true;
    }

    public function restore(User $user, FirewallRule $rule): bool
    {
        return false;
    }

    public function forceDelete(User $user, FirewallRule $rule): bool
    {
        return false;
    }
}
