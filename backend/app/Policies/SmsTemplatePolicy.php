<?php

namespace App\Policies;

use App\Models\SmsTemplate;
use App\Models\User;

class SmsTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SmsTemplate $smsTemplate): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SmsTemplate $smsTemplate): bool
    {
        return $user->isAdmin();
    }
}
