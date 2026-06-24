<?php

namespace App\Policies;

use App\Models\GlobalBlacklist;
use App\Models\User;

class GlobalBlacklistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, GlobalBlacklist $globalBlacklist): bool
    {
        return $user->isAdmin();
    }
}
