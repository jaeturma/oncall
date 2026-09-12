<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserReport;

class UserReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserReport $userReport): bool
    {
        return $user->canAccessAdmin() || $userReport->reporter_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canUseMarketplace();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserReport $userReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserReport $userReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserReport $userReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserReport $userReport): bool
    {
        return false;
    }
}
