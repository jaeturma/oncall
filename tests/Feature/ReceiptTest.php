<?php

namespace Tests\Feature;

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
use App\Services\JobPaymentService;
use App\Services\JobService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_customer_can_view_their_own_receipt(): void
    {
        [$job, $finder] = $this->paidJob();
        Sanctum::actingAs($finder);

        $response = $this->getJson("/api/v1/payments/{$job->jobPayment->id}/receipt");

        $response->assertOk();
        $response->assertJsonPath('data.receipt_number', $job->jobPayment->fresh()->receipt_number);
        $response->assertJsonPath('data.net_amount', '850.00');
    }

    public function test_the_provider_can_view_the_receipt(): void
    {
        [$job, , $provider] = $this->paidJob();
        Sanctum::actingAs($provider);

        $this->getJson("/api/v1/payments/{$job->jobPayment->id}/receipt")->assertOk();
    }

    public function test_a_back_office_staff_member_can_view_any_receipt(): void
    {
        // Accounting is back-office-only, not mobile-capable — reaches the
        // receipt through the web route, same boundary as every other
        // back-office surface in this app.
        [$job] = $this->paidJob();
        $accounting = User::factory()->create(['role' => UserRole::Accounting, 'status' => UserStatus::Active]);

        $this->actingAs($accounting)->get(route('payments.receipt', $job->jobPayment))->assertOk();
    }

    public function test_a_stranger_cannot_view_the_receipt(): void
    {
        [$job] = $this->paidJob();
        $stranger = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($stranger);

        $this->getJson("/api/v1/payments/{$job->jobPayment->id}/receipt")->assertForbidden();
    }

    /**
     * @return array{0: Job, 1: User, 2: User}
     */
    private function paidJob(): array
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
        app(JobPaymentService::class)->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');

        return [$job->fresh(), $finder, $provider];
    }
}
