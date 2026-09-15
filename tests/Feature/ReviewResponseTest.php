<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\Review;
use App\Models\ReviewSetting;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewed_provider_can_respond_once(): void
    {
        [$finder, $provider, $review] = $this->completedJobWithReview();

        $this->actingAs($provider)->post(route('reviews.response.store', $review), ['response' => 'Thank you for the kind words!'])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'response' => 'Thank you for the kind words!']);
        $this->assertTrue($review->fresh()->hasResponse());
    }

    public function test_provider_cannot_respond_twice(): void
    {
        [, $provider, $review] = $this->completedJobWithReview();
        $this->actingAs($provider)->post(route('reviews.response.store', $review), ['response' => 'First response.']);

        $this->actingAs($provider)->post(route('reviews.response.store', $review), ['response' => 'Second response.'])->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'response' => 'First response.']);
    }

    public function test_only_the_reviewed_provider_can_respond(): void
    {
        [$finder, , $review] = $this->completedJobWithReview();

        $this->actingAs($finder)->post(route('reviews.response.store', $review), ['response' => 'Not allowed.'])->assertForbidden();
        $outsider = User::factory()->serviceProvider()->create();
        $this->actingAs($outsider)->post(route('reviews.response.store', $review), ['response' => 'Not allowed either.'])->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'response' => null]);
    }

    public function test_provider_response_never_changes_the_customers_rating(): void
    {
        [, $provider, $review] = $this->completedJobWithReview();
        $originalRating = $review->rating;

        $this->actingAs($provider)->post(route('reviews.response.store', $review), ['response' => 'Thanks!']);

        $this->assertSame($originalRating, $review->fresh()->rating);
    }

    public function test_response_disabled_by_setting_is_rejected(): void
    {
        ReviewSetting::current()->update(['provider_response_enabled' => false]);
        [, $provider, $review] = $this->completedJobWithReview();

        $this->actingAs($provider)->post(route('reviews.response.store', $review), ['response' => 'Thanks!'])->assertForbidden();
    }

    public function test_response_length_is_validated(): void
    {
        [, $provider, $review] = $this->completedJobWithReview();

        $this->actingAs($provider)->post(route('reviews.response.store', $review), ['response' => str_repeat('a', 1001)])->assertSessionHasErrors('response');
    }

    /** @return array{User, User, Review} */
    private function completedJobWithReview(): array
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id]);
        $job = Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => JobStatus::Completed, 'completed_at' => now()]);
        $this->actingAs($finder)->post(route('jobs.reviews.store', $job), ['rating' => 5, 'comment' => 'Great work.']);
        $review = Review::query()->where('job_id', $job->id)->firstOrFail();

        return [$finder->refresh(), $provider->refresh(), $review];
    }
}
