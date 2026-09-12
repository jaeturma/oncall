<?php

namespace App\Policies;

use App\Enums\RestrictedCapability;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $withdrawal->user_id === $user->id || $user->canAccessBackOffice();
    }

    public function create(User $user): bool
    {
        return $user->status === UserStatus::Active
            && ! $user->isCapabilityRestricted(RestrictedCapability::Withdrawals);
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

        return $user->canAccessAdmin() || $user->role === $withdrawal->status->actingRole();
    }

    public function viewQueue(User $user): bool
    {
        return $user->canAccessBackOffice();
    }
}
