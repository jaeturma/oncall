<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EnforcementCase;
use App\Models\User;

class EnforcementCasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EnforcementCase $enforcementCase): bool
    {
        return $user->role === UserRole::Admin || $enforcementCase->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EnforcementCase $enforcementCase): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EnforcementCase $enforcementCase): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EnforcementCase $enforcementCase): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EnforcementCase $enforcementCase): bool
    {
        return false;
    }
}
