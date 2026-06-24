<?php

namespace App\Policies;

use App\Models\Segment;
use App\Models\User;

class SegmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Segment $segment): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Segment $segment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Segment $segment): bool
    {
        return $user->isAdmin();
    }
}
