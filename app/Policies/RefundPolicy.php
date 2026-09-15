<?php

namespace App\Policies;

use App\Enums\RefundStatus;
use App\Models\JobPayment;
use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function create(User $user, JobPayment $payment): bool
    {
        return (new JobPaymentPolicy)->requestRefund($user, $payment);
    }

    public function decide(User $user, Refund $refund): bool
    {
        return in_array($user->role, JobPaymentPolicy::RELEASERS, true)
            && in_array($refund->status, [RefundStatus::Requested, RefundStatus::UnderReview], true);
    }

    public function viewQueue(User $user): bool
    {
        return $user->canAccessBackOffice();
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->id === $refund->requested_by
            || $user->id === $refund->jobPayment->provider_id
            || $user->canAccessBackOffice();
    }
}
