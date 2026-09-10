<?php

namespace App\Policies;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Review $review): bool
    {
        return $user->can('view', $review->job);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Job $job): bool
    {
        return $job->status === JobStatus::Completed
            && in_array($user->id, [$job->service_finder_id, $job->provider_id], true)
            && ! $job->reviews()->where('reviewer_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Review $review): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Review $review): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Review $review): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Review $review): bool
    {
        return false;
    }
}
