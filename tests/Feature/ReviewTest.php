<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_finder_can_review_provider_after_completion_and_cached_rating_updates(): void
    {
        [$finder, $provider, $job] = $this->participantsAndJob(JobStatus::Completed);
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'rating_cached' => null]);

        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5, 'comment' => 'Excellent and careful work.'])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['job_id' => $job->id, 'reviewer_id' => $finder->id, 'reviewee_id' => $provider->id, 'rating' => 5]);
        $this->assertSame('5.00', $profile->fresh()->rating_cached);
    }

    public function test_provider_can_review_finder_after_completion(): void
    {
        [$finder, $provider, $job] = $this->participantsAndJob(JobStatus::Completed);

        $this->actingAs($provider)->post(route('jobs.reviews.store', $job), ['rating' => 4, 'comment' => 'Clear instructions.'])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['job_id' => $job->id, 'reviewer_id' => $provider->id, 'reviewee_id' => $finder->id, 'rating' => 4]);
    }

    public function test_active_job_cannot_be_reviewed(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::InProgress);

        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_outsider_cannot_review_job(): void
    {
        [, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $outsider = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($outsider)->post(route('jobs.reviews.store', $job), ['rating' => 1])->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_participant_can_submit_only_one_review_per_job(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5])->assertRedirect();

        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 1])->assertForbidden();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['job_id' => $job->id, 'reviewer_id' => $finder->id, 'rating' => 5]);
    }

    public function test_rating_and_comment_are_validated(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);

        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 6, 'comment' => str_repeat('a', 2001)])->assertSessionHasErrors(['rating', 'comment']);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_comment_is_escaped_on_job_screen(): void
    {
        [$finder, , $job] = $this->participantsAndJob(JobStatus::Completed);
        $comment = '<script>alert("review")</script> Good work';
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 4, 'comment' => $comment]);

        $this->actingAs($finder)->get(route('jobs.show', $job))->assertOk()->assertSee($comment)->assertDontSee($comment, false);
    }

    /** @return array{User, User, Job} */
    private function participantsAndJob(JobStatus $status): array
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id]);
        $job = Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => $status, 'completed_at' => $status === JobStatus::Completed ? now() : null]);

        return [$finder->refresh(), $provider->refresh(), $job];
    }
}
