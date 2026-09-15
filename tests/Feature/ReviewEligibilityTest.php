<?php

namespace Tests\Feature;

use App\Enums\DisputeCategory;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\ReviewSetting;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DisputeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase P §8/§60 — the full eligibility checklist, exercised through the
 * mobile-API eligibility endpoint (GET /jobs/{job}/review-eligibility) and,
 * for the actual submission-blocking behavior, the existing
 * jobs.reviews.store route. Laravel decides; nothing here trusts the client.
 */
class ReviewEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_job_participant_can_review(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);
        Sanctum::actingAs($finder);

        $this->getJson(route('api.jobs.review-eligibility', $job))->assertOk()->assertJsonPath('data.can_review', true);
    }

    public function test_non_completed_job_is_not_reviewable(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::InProgress);
        Sanctum::actingAs($finder);

        $this->getJson(route('api.jobs.review-eligibility', $job))->assertOk()->assertJsonPath('data.can_review', false);
    }

    public function test_cancelled_job_is_not_reviewable(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Cancelled);
        Sanctum::actingAs($finder);

        $this->getJson(route('api.jobs.review-eligibility', $job))->assertOk()->assertJsonPath('data.can_review', false);
    }

    public function test_unrelated_customer_cannot_review(): void
    {
        [, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $outsider = User::factory()->create(['role' => UserRole::ServiceFinder]);
        Sanctum::actingAs($outsider);

        // Not a job participant — JobPolicy::view already refuses this user
        // the job entirely (403), a stronger guard than a false can_review.
        $this->getJson(route('api.jobs.review-eligibility', $job))->assertForbidden();
        $this->actingAs($outsider)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertForbidden();
    }

    public function test_sponsor_relationship_alone_does_not_grant_eligibility(): void
    {
        [, $provider, $job] = $this->participantsAndJob(JobStatus::Completed);
        $sponsoredOutsider = User::factory()->create(['role' => UserRole::ServiceFinder, 'sponsor_user_id' => $provider->id]);
        Sanctum::actingAs($sponsoredOutsider);

        $this->getJson(route('api.jobs.review-eligibility', $job))->assertForbidden();
        $this->actingAs($sponsoredOutsider)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertForbidden();
        $this->assertDatabaseMissing('reviews', ['job_id' => $job->id, 'reviewer_id' => $sponsoredOutsider->id]);
    }

    public function test_provider_cannot_review_via_a_job_that_is_not_their_own(): void
    {
        [, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $anotherProvider = User::factory()->serviceProvider()->create();

        $this->actingAs($anotherProvider)->post(route('jobs.reviews.store', $job), ['rating' => 1])->assertForbidden();
        $this->assertDatabaseMissing('reviews', ['job_id' => $job->id, 'reviewer_id' => $anotherProvider->id]);
    }

    public function test_duplicate_review_is_rejected(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);
        Sanctum::actingAs($finder);
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5]);

        $this->getJson(route('api.jobs.review-eligibility', $job))->assertOk()->assertJsonPath('data.can_review', false)->assertJsonPath('data.reviewed', true);
        // The FormRequest's policy-backed authorize() gate rejects the
        // duplicate before the service's own 409-on-reviewed check is ever
        // reached — 403 here matches the pre-existing duplicate-review test.
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 1])->assertForbidden();
    }

    public function test_manipulated_job_id_that_does_not_belong_to_the_user_is_rejected(): void
    {
        [, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $attacker = User::factory()->create(['role' => UserRole::ServiceFinder]);

        // Attacker tries to submit a review by guessing another user's job id.
        $this->actingAs($attacker)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertForbidden();
        $this->assertDatabaseMissing('reviews', ['job_id' => $job->id, 'reviewer_id' => $attacker->id]);
    }

    public function test_review_window_expiry_is_enforced(): void
    {
        ReviewSetting::current()->update(['review_window_days' => 7]);
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $job->update(['completed_at' => now()->subDays(10)]);
        Sanctum::actingAs($finder);

        $this->getJson(route('api.jobs.review-eligibility', $job))->assertOk()->assertJsonPath('data.can_review', false);
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertForbidden();
    }

    public function test_review_within_window_is_still_allowed(): void
    {
        ReviewSetting::current()->update(['review_window_days' => 30]);
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $job->update(['completed_at' => now()->subDays(29)]);

        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['job_id' => $job->id, 'reviewer_id' => $finder->id]);
    }

    public function test_disputed_job_cannot_be_reviewed_even_if_it_was_completed_before(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);
        app(DisputeService::class)->open($job, $finder, DisputeCategory::Other, 'a reason long enough here');
        Sanctum::actingAs($finder);

        $this->getJson(route('api.jobs.review-eligibility', $job))->assertOk()->assertJsonPath('data.can_review', false);
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertForbidden();
    }

    public function test_reviews_disabled_setting_blocks_submission(): void
    {
        ReviewSetting::current()->update(['reviews_enabled' => false]);
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);

        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertForbidden();
    }

    /** @return array{User, User, Job} */
    private function participantsAndJob(JobStatus $status): array
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'status' => ServiceRequestStatus::Accepted]);
        $job = Job::factory()->create([
            'service_request_id' => $serviceRequest->id,
            'service_finder_id' => $finder->id,
            'provider_id' => $provider->id,
            'status' => $status,
            'completed_at' => $status === JobStatus::Completed ? now() : null,
        ]);

        return [$finder->refresh(), $provider->refresh(), $job];
    }
}
