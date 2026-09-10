<?php

namespace App\Policies;

use App\Enums\RestrictedCapability;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    private const STAFF = [UserRole::Admin, UserRole::Accounting, UserRole::Budget, UserRole::Cashier];

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $withdrawal->user_id === $user->id || in_array($user->role, self::STAFF, true);
    }

    public function create(User $user): bool
    {
        return $user->status === UserStatus::Active
            && ! $user->isCapabilityRestricted(RestrictedCapability::FullAccountAccess);
    }

    public function cancel(User $user, Withdrawal $withdrawal): bool
    {
        return $withdrawal->user_id === $user->id
            && $withdrawal->status === WithdrawalStatus::AccountingReview;
    }

    /** Act on the current review step (approve / return / reject). */
    public function review(User $user, Withdrawal $withdrawal): bool
    {
        if (! $withdrawal->status->isOpen()) {
            return false;
        }

        return $user->role === UserRole::Admin || $user->role === $withdrawal->status->actingRole();
    }

    public function viewQueue(User $user): bool
    {
        return in_array($user->role, self::STAFF, true);
    }
}
