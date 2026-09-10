<?php

namespace App\Policies;

use App\Enums\RestrictedCapability;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ServiceFinder, UserRole::ServiceProvider], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Job $job): bool
    {
        return $job->service_finder_id === $user->id || $job->provider_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Job $job): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Job $job): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Job $job): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Job $job): bool
    {
        return false;
    }

    public function transition(User $user, Job $job): bool
    {
        return $this->view($user, $job) && $job->allowedTransitionsFor($user) !== [];
    }

    public function revealContact(User $user, Job $job): bool
    {
        return $this->view($user, $job) && ! $user->isCapabilityRestricted(RestrictedCapability::ContactReveal);
    }
}
