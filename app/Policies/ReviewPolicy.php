<?php

namespace App\Policies;

use App\Enums\ReviewStatus;
use App\Models\Job;
use App\Models\Review;
use App\Models\ReviewSetting;
use App\Models\User;
use App\Services\ReviewEligibilityService;

class ReviewPolicy
{
    public function __construct(private readonly ReviewEligibilityService $eligibility) {}

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
     * Determine whether the user can create models. Delegates to
     * {@see ReviewEligibilityService}, the single source of truth for
     * review eligibility (Phase P §8) — no duplicated checks here.
     */
    public function create(User $user, Job $job): bool
    {
        return $this->eligibility->canReview($job, $user)['can_review'];
    }

    /**
     * Determine whether the user can update the model. Reviews stay
     * immutable (Phase P decision, matches the existing pre-Phase-P
     * policy) — withdrawal is the only reviewer-initiated change, via
     * {@see withdraw()}.
     */
    public function update(User $user, Review $review): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model. No hard delete —
     * see {@see withdraw()} for the soft-removal path.
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

    /** Only the reviewer, and only while the review is still published. */
    public function withdraw(User $user, Review $review): bool
    {
        return $review->reviewer_id === $user->id && $review->status === ReviewStatus::Published;
    }

    /** Only the reviewed provider, and only once. */
    public function respond(User $user, Review $review): bool
    {
        return ReviewSetting::current()->provider_response_enabled
            && $review->reviewee_id === $user->id
            && ! $review->hasResponse();
    }

    /** Any marketplace user may report a published review. */
    public function report(User $user, Review $review): bool
    {
        return $user->canUseMarketplace() && $review->status === ReviewStatus::Published;
    }
}
