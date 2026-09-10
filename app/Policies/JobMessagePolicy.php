<?php

namespace App\Policies;

use App\Enums\RestrictedCapability;
use App\Models\Job;
use App\Models\JobMessage;
use App\Models\User;

class JobMessagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobMessage $jobMessage): bool
    {
        return $user->can('view', $jobMessage->job);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Job $job): bool
    {
        return $user->can('view', $job) && ! $user->isCapabilityRestricted(RestrictedCapability::Messaging);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobMessage $jobMessage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobMessage $jobMessage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JobMessage $jobMessage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, JobMessage $jobMessage): bool
    {
        return false;
    }
}
