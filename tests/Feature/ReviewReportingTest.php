<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_user_can_report_a_published_review(): void
    {
        [, , $review] = $this->completedJobWithReview();
        $reporter = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($reporter)->post(route('reviews.reports.store', $review), ['category' => 'SPAM', 'description' => 'Looks like spam.'])->assertRedirect();

        $this->assertDatabaseHas('review_reports', ['review_id' => $review->id, 'reporter_id' => $reporter->id, 'category' => 'SPAM', 'status' => 'SUBMITTED']);
    }

    public function test_duplicate_report_from_the_same_reporter_is_idempotent_not_a_500(): void
    {
        [, , $review] = $this->completedJobWithReview();
        $reporter = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $this->actingAs($reporter)->post(route('reviews.reports.store', $review), ['category' => 'SPAM']);

        $this->actingAs($reporter)->post(route('reviews.reports.store', $review), ['category' => 'HARASSMENT'])->assertRedirect();

        $this->assertDatabaseCount('review_reports', 1);
    }

    public function test_reporting_a_review_does_not_hide_it(): void
    {
        [, , $review] = $this->completedJobWithReview();
        $reporter = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($reporter)->post(route('reviews.reports.store', $review), ['category' => 'OTHER']);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'PUBLISHED']);
    }

    public function test_admin_can_hide_a_review(): void
    {
        [, , $review] = $this->completedJobWithReview();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('admin.reviews.moderate', $review), ['status' => 'HIDDEN', 'notes' => 'Contains personal info.'])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'HIDDEN']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'review.moderated', 'subject_id' => $review->id]);
    }

    public function test_admin_can_restore_a_hidden_review(): void
    {
        [, , $review] = $this->completedJobWithReview();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->patch(route('admin.reviews.moderate', $review), ['status' => 'HIDDEN']);

        $this->actingAs($admin)->patch(route('admin.reviews.moderate', $review), ['status' => 'PUBLISHED'])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'PUBLISHED']);
    }

    public function test_admin_can_resolve_a_report(): void
    {
        [, , $review] = $this->completedJobWithReview();
        $reporter = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $this->actingAs($reporter)->post(route('reviews.reports.store', $review), ['category' => 'SPAM']);
        $report = ReviewReport::query()->where('review_id', $review->id)->firstOrFail();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('admin.review-reports.update', $report), ['status' => 'DISMISSED', 'notes' => 'Not spam, dismissed.'])->assertRedirect();

        $this->assertDatabaseHas('review_reports', ['id' => $report->id, 'status' => 'DISMISSED', 'reviewed_by' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'review_report.resolved', 'subject_id' => $report->id]);
    }

    public function test_moderation_notes_and_reporter_identity_are_never_exposed_to_marketplace_viewers(): void
    {
        [, $provider, $review] = $this->completedJobWithReview();
        $reporter = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $this->actingAs($reporter)->post(route('reviews.reports.store', $review), ['category' => 'SPAM', 'description' => 'private detail about reporter']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->patch(route('admin.reviews.moderate', $review), ['status' => 'HIDDEN', 'notes' => 'secret internal note']);

        Sanctum::actingAs($provider);
        $response = $this->getJson(route('api.reviews.received'));

        $response->assertOk();
        $raw = json_encode($response->json());
        $this->assertStringNotContainsString('secret internal note', (string) $raw);
        $this->assertStringNotContainsString($reporter->name, (string) $raw);
    }

    public function test_marketplace_roles_cannot_moderate_reviews_or_view_reports(): void
    {
        [, , $review] = $this->completedJobWithReview();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($finder)->patch(route('admin.reviews.moderate', $review), ['status' => 'HIDDEN'])->assertForbidden();
        $this->actingAs($finder)->get(route('admin.review-reports.index'))->assertForbidden();
    }

    public function test_authorized_admin_can_view_review_reports(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.review-reports.index'))->assertOk();
    }

    public function test_mobile_sanctum_token_cannot_reach_web_moderation_routes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        // No /api/v1 route exists for this at all — web-only by construction.
        $this->getJson('/api/v1/admin/review-reports')->assertNotFound();
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
