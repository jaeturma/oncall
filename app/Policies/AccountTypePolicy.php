<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AccountType;
use App\Models\User;

class AccountTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, AccountType $accountType): bool
    {
        return $user->role === UserRole::Admin;
    }
}
