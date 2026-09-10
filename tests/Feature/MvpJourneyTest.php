<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\VerificationStatus;
use App\Models\Job;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MvpJourneyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_to_completed_and_rated_job_journey_succeeds_without_privacy_leak(): void
    {
        Storage::fake('local');
        $province = Province::factory()->create(['name' => 'Cebu']);
        $municipality = Municipality::factory()->for($province)->create(['name' => 'Cebu City']);
        $service = Service::factory()->create(['name' => 'Home Plumbing']);
        $provider = User::factory()->serviceProvider()->identityVerified()->create(['name' => 'Journey Provider', 'email' => 'provider@journey.test', 'phone' => '09171234567']);
        ProviderDocument::factory()->for($provider)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $profile = ProviderProfile::factory()->for($provider, 'user')->create(['province_id' => $province->id, 'municipality_id' => $municipality->id, 'verification_status' => VerificationStatus::Verified, 'available_now' => true]);
        $profile->providerServices()->create(['service_id' => $service->id, 'active' => true]);

        $this->get(route('providers.search', ['help' => 'service:'.$service->id, 'province_id' => $province->id]))
            ->assertOk()
            ->assertSee('Local Service Provider')
            ->assertDontSee($provider->name)
            ->assertDontSee($provider->email)
            ->assertDontSee($provider->phone);

        $this->post(route('register'), [
            'name' => 'Journey Finder',
            'email' => 'finder@journey.test',
            'role' => 'SERVICE_FINDER',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));
        $finder = User::where('email', 'finder@journey.test')->sole();
        $this->assertAuthenticatedAs($finder);

        $this->post(route('verification.documents.store'), [
            'document_type' => DocumentType::NationalId->value,
            'document' => UploadedFile::fake()->create('finder-id.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('verification.index'));
        $finderDocument = ProviderDocument::whereBelongsTo($finder)->sole();
        $this->assertSame(VerificationStatus::Submitted, $finderDocument->status);

        $admin = User::factory()->admin()->create()->refresh();
        $this->actingAs($admin)->patch(route('admin.verifications.update', $finderDocument), [
            'status' => VerificationStatus::Verified->value,
            'expires_at' => now()->addYear()->toDateString(),
        ])->assertRedirect();
        $this->assertSame(VerificationStatus::Verified, $finder->fresh()->identity_verification_status);

        $this->actingAs($finder->fresh())->post(route('service-requests.store', $profile), [
            'service_id' => $service->id,
            'province_id' => $province->id,
            'municipality_id' => $municipality->id,
            'title' => 'Repair a leaking kitchen pipe',
            'description' => 'Water is leaking below the kitchen sink.',
            'urgency' => 'SAME_DAY',
            'budget_min' => 800,
            'budget_max' => 1200,
            'safety_acknowledged' => '1',
        ])->assertRedirect();
        $serviceRequest = ServiceRequest::whereBelongsTo($finder, 'serviceFinder')->sole();
        $this->assertSame(ServiceRequestStatus::Requested, $serviceRequest->status);

        $this->actingAs($provider->fresh())->patch(route('service-requests.accept', $serviceRequest), ['agreed_price' => '1000.00'])->assertRedirect();
        $job = Job::whereBelongsTo($serviceRequest)->sole();
        $this->assertSame(JobStatus::Accepted, $job->status);
        $this->assertSame('1000.00', $job->agreed_price);

        foreach ([JobStatus::OnTheWay, JobStatus::InProgress, JobStatus::Completed] as $status) {
            $this->actingAs($provider->fresh())->patch(route('jobs.status.update', $job), ['status' => $status->value, 'notes' => 'Journey status confirmation.'])->assertRedirect();
            $this->assertSame($status, $job->fresh()->status);
        }
        $this->assertNotNull($job->fresh()->completed_at);
        $this->assertSame(4, $job->statusLogs()->count());

        $this->actingAs($finder->fresh())->post(route('jobs.reviews.store', $job), ['rating' => 5, 'comment' => 'Excellent service.'])->assertRedirect();
        $review = Review::whereBelongsTo($job)->sole();
        $this->assertSame($finder->id, $review->reviewer_id);
        $this->assertSame($provider->id, $review->reviewee_id);
        $this->assertSame('5.00', $profile->fresh()->rating_cached);
    }
}
