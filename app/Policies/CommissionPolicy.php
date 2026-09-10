<?php

namespace App\Policies;

use App\Enums\CommissionStatus;
use App\Enums\UserRole;
use App\Models\Commission;
use App\Models\User;

class CommissionPolicy
{
    private const REVIEWERS = [UserRole::Admin, UserRole::Accounting];

    public function viewAny(User $user): bool
    {
        return in_array($user->role, self::REVIEWERS, true);
    }

    public function approve(User $user, Commission $commission): bool
    {
        return in_array($user->role, self::REVIEWERS, true)
            && in_array($commission->status, [CommissionStatus::Pending, CommissionStatus::Approved], true);
    }

    public function reverse(User $user, Commission $commission): bool
    {
        return in_array($user->role, self::REVIEWERS, true)
            && $commission->status !== CommissionStatus::Reversed;
    }
}
