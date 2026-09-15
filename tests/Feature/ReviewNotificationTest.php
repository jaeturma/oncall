<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase P §36/§66 — review-domain notification dispatch reuses the exact
 * Phase M NotificationDispatcher mechanism already proven for jobs/disputes.
 */
class ReviewNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewee_is_notified_when_a_review_is_submitted(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id]);
        $job = Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => JobStatus::Completed, 'completed_at' => now()]);

        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5, 'comment' => 'Great work.']);

        $notification = $provider->notifications()->sole();
        $this->assertSame('review_received', $notification->data['key']);
        $this->assertSame('job', $notification->data['target']['screen']);
        $this->assertSame($job->id, $notification->data['target']['id']);
    }

    public function test_reviewer_is_notified_when_the_provider_responds(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id]);
        $job = Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => JobStatus::Completed, 'completed_at' => now()]);
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5]);
        $review = Review::query()->where('job_id', $job->id)->firstOrFail();

        $this->actingAs($provider)->post(route('reviews.response.store', $review), ['response' => 'Thank you!']);

        $notification = $finder->notifications()->where('data->key', 'review_response_received')->sole();
        $this->assertSame('review_response_received', $notification->data['key']);
    }
}
