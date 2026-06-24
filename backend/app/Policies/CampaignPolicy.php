<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /** Any authenticated user can list/view campaigns */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return true;
    }

    /** Only admins can create, edit, send, duplicate, export, or delete campaigns */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }

    public function send(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }

    public function duplicate(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }

    public function export(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }
}
