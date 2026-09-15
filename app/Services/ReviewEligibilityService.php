<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\ReviewSetting;
use App\Models\User;
use App\Policies\ReviewPolicy;
use Illuminate\Support\Carbon;

/**
 * The single source of truth for "may this user review this job's other
 * participant" (Phase P §8) — replaces the eligibility checks that used to
 * be duplicated between {@see ReviewPolicy} and
 * {@see ReviewService}. Both now call this instead of
 * re-deriving the rule.
 *
 * A review must come from a real completed Oncall transaction: the job must
 * exist, the caller must be one of its two actual participants, and it must
 * currently be in the exact Completed state (never Disputed, even if it was
 * Completed before a dispute was opened) — see JobStatus/DisputeService.
 */
class ReviewEligibilityService
{
    /**
     * @return array{can_review: bool, reviewed: bool, expires_at: ?Carbon, reason: ?string}
     */
    public function canReview(Job $job, User $user): array
    {
        $isParticipant = in_array($user->id, [$job->service_finder_id, $job->provider_id], true);

        if (! $isParticipant) {
            return $this->result(false, false, null, 'not_a_participant');
        }

        $reviewed = $job->reviews()->where('reviewer_id', $user->id)->exists();

        if ($reviewed) {
            return $this->result(false, true, null, 'already_reviewed');
        }

        if ($job->status !== JobStatus::Completed) {
            return $this->result(false, false, null, 'job_not_completed');
        }

        $settings = ReviewSetting::current();

        if (! $settings->reviews_enabled) {
            return $this->result(false, false, null, 'reviews_disabled');
        }

        $expiresAt = $job->completed_at?->copy()->addDays($settings->review_window_days);

        if ($expiresAt !== null && now()->greaterThan($expiresAt)) {
            return $this->result(false, false, $expiresAt, 'review_window_expired');
        }

        return $this->result(true, false, $expiresAt, null);
    }

    /**
     * @return array{can_review: bool, reviewed: bool, expires_at: ?Carbon, reason: ?string}
     */
    private function result(bool $canReview, bool $reviewed, ?Carbon $expiresAt, ?string $reason): array
    {
        return ['can_review' => $canReview, 'reviewed' => $reviewed, 'expires_at' => $expiresAt, 'reason' => $reason];
    }
}
