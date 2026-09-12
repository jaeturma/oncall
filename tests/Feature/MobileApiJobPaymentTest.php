<?php

namespace Tests\Feature;

use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Job;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\JobService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase F surfaced this gap: Phase D's mobile API never exposed payment
 * confirmation, so a customer could never complete a booking's payment step
 * from the Flutter app. This is the customer confirming they paid the
 * provider — part of the booking lifecycle, not a back-office action.
 */
class MobileApiJobPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_finder_can_confirm_payment_for_their_own_job(): void
    {
        [$job, $finder] = $this->completedJob();
        Sanctum::actingAs($finder);

        $response = $this->patchJson("/api/v1/job-payments/{$job->jobPayment->id}/confirm", [
            'payment_method' => 'GCash',
            'payment_reference' => 'GC-12345',
        ]);

        $response->assertOk()->assertJsonPath('data.status', JobPaymentStatus::Paid->value);
        $this->assertDatabaseHas('job_payments', ['id' => $job->jobPayment->id, 'status' => JobPaymentStatus::Paid->value]);
    }

    public function test_someone_else_cannot_confirm_payment(): void
    {
        [$job] = $this->completedJob();
        $stranger = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($stranger);

        $this->patchJson("/api/v1/job-payments/{$job->jobPayment->id}/confirm", [
            'payment_method' => 'GCash',
            'payment_reference' => 'GC-12345',
        ])->assertForbidden();
    }

    /** @return array{Job, User, User} */
    private function completedJob(): array
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);
        ProviderProfile::factory()->for($provider, 'user')->create(['province_id' => $province->id, 'municipality_id' => $municipality->id]);

        $request = ServiceRequest::create([
            'service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'service_id' => $service->id,
            'province_id' => $province->id, 'municipality_id' => $municipality->id, 'title' => 'Test job',
            'urgency' => ServiceUrgency::SameDay, 'status' => ServiceRequestStatus::Accepted, 'safety_acknowledged_at' => now(),
        ]);
        $job = Job::create([
            'service_request_id' => $request->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id,
            'agreed_price' => '1000.00', 'status' => JobStatus::InProgress, 'accepted_at' => now(), 'started_at' => now(),
        ]);

        app(JobService::class)->transition($job, $provider, JobStatus::Completed, null);

        return [$job->fresh(), $finder, $provider];
    }
}
