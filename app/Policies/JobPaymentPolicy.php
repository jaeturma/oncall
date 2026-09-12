<?php

namespace App\Policies;

use App\Enums\JobPaymentStatus;
use App\Enums\UserRole;
use App\Models\JobPayment;
use App\Models\User;

class JobPaymentPolicy
{
    private const RELEASERS = [UserRole::Admin, UserRole::Accounting];

    public function confirm(User $user, JobPayment $payment): bool
    {
        return $payment->status === JobPaymentStatus::Pending
            && $payment->job->service_finder_id === $user->id;
    }

    public function release(User $user, JobPayment $payment): bool
    {
        return in_array($user->role, self::RELEASERS, true)
            && $payment->status === JobPaymentStatus::Paid
            && ! $payment->job->dispute?->isOpen();
    }

    public function reverse(User $user, JobPayment $payment): bool
    {
        return in_array($user->role, self::RELEASERS, true)
            && $payment->status !== JobPaymentStatus::Reversed;
    }

    public function viewQueue(User $user): bool
    {
        return $user->canAccessBackOffice();
    }
}
