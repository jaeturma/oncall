<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\Job;
use App\Models\Review;
use App\Models\ReviewSetting;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(
        private readonly ReviewEligibilityService $eligibility,
        private readonly ProviderReputationService $reputation,
        private readonly NotificationDispatcher $notifications,
    ) {}

    /** @param array{rating: int, comment?: string|null} $attributes */
    public function create(Job $job, User $reviewer, array $attributes): Review
    {
        return DB::transaction(function () use ($job, $reviewer, $attributes): Review {
            $lockedJob = Job::whereKey($job)->lockForUpdate()->firstOrFail();

            // Re-checked here (not just in the policy) as defense in depth
            // against a policy/service drift — see ReviewEligibilityService.
            $eligible = $this->eligibility->canReview($lockedJob, $reviewer);
            abort_unless($eligible['can_review'], $eligible['reviewed'] ? 409 : 403);

            $revieweeId = $reviewer->id === $lockedJob->service_finder_id ? $lockedJob->provider_id : $lockedJob->service_finder_id;
            $review = $lockedJob->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $revieweeId,
                'status' => ReviewStatus::Published,
                ...$attributes,
            ]);

            $reviewee = User::findOrFail($revieweeId);
            $this->reputation->recalculate($reviewee);

            $this->notifications->dispatch(
                $reviewee,
                'review_received',
                ['reviewer_name' => $reviewer->name, 'rating' => (string) $review->rating],
                ['screen' => 'job', 'id' => $lockedJob->id],
                dedupKey: "review_received:review:{$review->id}",
            );

            return $review;
        });
    }

    /**
     * Reviewer-initiated soft removal — reviews stay immutable (no edit
     * capability, Phase P decision), but a reviewer may withdraw their own
     * review. Withdrawn reviews are excluded from public listings and
     * reputation aggregates, never hard-deleted (Phase P §16).
     */
    public function withdraw(Review $review, User $user): Review
    {
        return DB::transaction(function () use ($review, $user): Review {
            $lockedReview = Review::whereKey($review)->lockForUpdate()->firstOrFail();
            abort_unless($lockedReview->reviewer_id === $user->id && $lockedReview->status === ReviewStatus::Published, 403);

            $lockedReview->update(['status' => ReviewStatus::Withdrawn]);
            $this->reputation->recalculate(User::findOrFail($lockedReview->reviewee_id));

            return $lockedReview;
        });
    }

    /**
     * The reviewed provider's single public response (Phase P §18) — never
     * touches `rating`, and only one response is ever allowed per review.
     */
    public function respond(Review $review, User $provider, string $response): Review
    {
        return DB::transaction(function () use ($review, $provider, $response): Review {
            $lockedReview = Review::whereKey($review)->lockForUpdate()->firstOrFail();
            abort_unless(ReviewSetting::current()->provider_response_enabled, 403);
            abort_unless($lockedReview->reviewee_id === $provider->id, 403);
            abort_if($lockedReview->hasResponse(), 409);

            $lockedReview->update(['response' => $response, 'responded_at' => now()]);

            $this->notifications->dispatch(
                $lockedReview->reviewer,
                'review_response_received',
                ['provider_name' => $provider->name],
                ['screen' => 'job', 'id' => $lockedReview->job_id],
                dedupKey: "review_response_received:review:{$lockedReview->id}",
            );

            return $lockedReview;
        });
    }

    /**
     * Admin-only moderation (route/gate-checked by the caller). `WITHDRAWN`
     * is deliberately not a valid target here — that transition is
     * reviewer-only via {@see withdraw()}.
     */
    public function moderate(Review $review, ReviewStatus $status): Review
    {
        return DB::transaction(function () use ($review, $status): Review {
            $lockedReview = Review::whereKey($review)->lockForUpdate()->firstOrFail();
            $lockedReview->update(['status' => $status, 'moderated_at' => now()]);
            $this->reputation->recalculate(User::findOrFail($lockedReview->reviewee_id));

            return $lockedReview;
        });
    }
}
