<?php

namespace App\Observers;

use App\Enums\VerificationStatus;
use App\Models\User;
use App\Services\CommissionEngine;

class UserObserver
{
    public function __construct(private readonly CommissionEngine $commissions) {}

    /**
     * When a user's identity becomes verified, post any eligible sponsor
     * commission. Runs inside the caller's transaction, so it rolls back with it.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('identity_verification_status')
            && $user->identity_verification_status === VerificationStatus::Verified) {
            $this->commissions->handleUserVerified($user);
        }
    }
}
