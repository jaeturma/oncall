<?php

namespace App\Policies;

use App\Models\ReviewReport;
use App\Models\User;

class ReviewReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, ReviewReport $report): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canUseMarketplace();
    }

    public function update(User $user, ReviewReport $report): bool
    {
        return $user->canAccessAdmin();
    }
}
