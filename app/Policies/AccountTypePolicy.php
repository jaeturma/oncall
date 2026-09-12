<?php

namespace App\Policies;

use App\Models\AccountType;
use App\Models\User;

class AccountTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, AccountType $accountType): bool
    {
        return $user->canAccessAdmin();
    }
}
