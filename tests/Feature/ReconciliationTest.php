<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ReconciliationCategory;
use App\Enums\ReconciliationStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Job;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\ReconciliationFlag;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\JobPaymentService;
use App\Services\JobService;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_paid_but_unreleased_payment_is_flagged_stale_after_the_window(): void
    {
        [$job, $finder] = $this->completedJob('1000.00');
        app(JobPaymentService::class)->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');
        $job->jobPayment->fresh()->update(['confirmed_at' => now()->subHours(80)]);

        $flags = app(ReconciliationService::class)->flag();

        $this->assertTrue($flags->contains(fn (ReconciliationFlag $flag) => $flag->category === ReconciliationCategory::StalePayment && $flag->job_payment_id === $job->jobPayment->id));
    }

    public function test_a_recently_paid_payment_is_not_flagged(): void
    {
        [$job] = $this->completedJob('1000.00');

        $flags = app(ReconciliationService::class)->flag();

        $this->assertFalse($flags->contains(fn (ReconciliationFlag $flag) => $flag->category === ReconciliationCategory::StalePayment));
    }

    public function test_duplicate_gateway_references_across_payments_are_flagged(): void
    {
        [$jobA, $finderA] = $this->completedJob('500.00');
        [$jobB, $finderB] = $this->completedJob('500.00');
        app(JobPaymentService::class)->confirmPaid($jobA->jobPayment, $finderA, 'GCash', 'SHARED-REF');
        app(JobPaymentService::class)->confirmPaid($jobB->jobPayment, $finderB, 'GCash', 'SHARED-REF');

        // The gateway_reference lives on the PaymentAttempt; the manual
        // gateway echoes back whatever reference the customer submitted.
        $flags = app(ReconciliationService::class)->flag();

        $this->assertTrue($flags->contains(fn (ReconciliationFlag $flag) => $flag->category === ReconciliationCategory::DuplicateReference));
    }

    public function test_running_reconciliation_twice_does_not_duplicate_flags(): void
    {
        [$job, $finder] = $this->completedJob('1000.00');
        app(JobPaymentService::class)->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');
        $job->jobPayment->fresh()->update(['confirmed_at' => now()->subHours(80)]);
        $service = app(ReconciliationService::class);

        $service->flag();
        $service->flag();

        $this->assertSame(1, ReconciliationFlag::where('category', ReconciliationCategory::StalePayment)->count());
    }

    public function test_admin_can_view_and_resolve_the_reconciliation_queue(): void
    {
        [$job, $finder] = $this->completedJob('1000.00');
        app(JobPaymentService::class)->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');
        $job->jobPayment->fresh()->update(['confirmed_at' => now()->subHours(80)]);
        app(ReconciliationService::class)->flag();
        $flag = ReconciliationFlag::sole();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.finance.reconciliation.index'))->assertOk();
        $this->actingAs($admin)->patch(route('admin.finance.reconciliation.resolve', $flag), ['resolution_notes' => 'Released manually after review.'])->assertRedirect();

        $this->assertSame(ReconciliationStatus::Resolved, $flag->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'reconciliation_flag.resolved']);
    }

    public function test_marketplace_role_cannot_view_the_reconciliation_queue(): void
    {
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);

        $this->actingAs($provider)->get(route('admin.finance.reconciliation.index'))->assertForbidden();
    }

    /**
     * @return array{0: Job, 1: User, 2: User}
     */
    private function completedJob(string $agreedPrice): array
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
            'agreed_price' => $agreedPrice, 'status' => JobStatus::InProgress, 'accepted_at' => now(), 'started_at' => now(),
        ]);

        app(JobService::class)->transition($job, $provider, JobStatus::Completed, null);

        return [$job->fresh(), $finder, $provider];
    }
}
