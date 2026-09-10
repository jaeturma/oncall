<?php

namespace Tests\Feature;

use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Dispute;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DisputeService;
use App\Services\JobPaymentService;
use App\Services\JobService;
use App\Services\WalletLedger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DisputeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_participant_opens_a_dispute_and_the_job_becomes_disputed(): void
    {
        [$job, $finder, $provider] = $this->paidJob();

        $this->actingAs($finder)->post(route('disputes.store', $job), [
            'category' => DisputeCategory::ServiceNotAsAgreed->value,
            'description' => 'The finished work does not match what we agreed.',
        ])->assertRedirect();

        $dispute = Dispute::sole();
        $this->assertSame(JobStatus::Disputed, $job->fresh()->status);
        $this->assertSame(DisputeStatus::Open, $dispute->status);
        $this->assertSame($provider->id, $dispute->against_user_id);
        $this->assertSame(JobStatus::Completed, $dispute->job_prior_status);
    }

    public function test_a_non_participant_cannot_open_a_dispute_and_it_cannot_be_opened_twice(): void
    {
        [$job, $finder] = $this->paidJob();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->post(route('disputes.store', $job), ['category' => DisputeCategory::Other->value, 'description' => str_repeat('x', 25)])->assertForbidden();

        app(DisputeService::class)->open($job, $finder, DisputeCategory::Other, 'first dispute reason here');
        $this->actingAs($finder)->post(route('disputes.store', $job), ['category' => DisputeCategory::Other->value, 'description' => str_repeat('y', 25)])->assertForbidden();
    }

    public function test_an_open_dispute_freezes_the_job_payment_release(): void
    {
        [$job, $finder, $provider] = $this->paidJob();
        app(DisputeService::class)->open($job, $finder, DisputeCategory::PaymentIssue, 'payment amount is wrong here');
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);

        $this->assertFalse($accounting->can('release', $job->jobPayment->fresh()));

        $this->expectExceptionMessage('open dispute');
        app(JobPaymentService::class)->release($job->jobPayment->fresh(), $accounting);
    }

    public function test_rejecting_a_dispute_restores_the_job_and_unfreezes_payment(): void
    {
        [$job, $finder] = $this->paidJob();
        $dispute = app(DisputeService::class)->open($job, $finder, DisputeCategory::Other, 'a reason long enough here');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('admin.disputes.update', $dispute), ['action' => 'reject', 'resolution' => 'No evidence the work was deficient.'])->assertRedirect();

        $this->assertSame(DisputeStatus::Rejected, $dispute->fresh()->status);
        $this->assertSame(JobStatus::Completed, $job->fresh()->status);
        $this->assertTrue(User::factory()->create(['role' => UserRole::Accounting])->can('release', $job->jobPayment->fresh()));
    }

    public function test_upholding_a_dispute_reverses_the_payment_and_cancels_the_job(): void
    {
        [$job, $finder, $provider] = $this->paidJob(releasePayment: true);
        $this->assertSame('680.00', app(WalletLedger::class)->availableBalance($provider));
        $dispute = app(DisputeService::class)->open($job, $finder, DisputeCategory::ServiceNotAsAgreed, 'work was not done at all here');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        app(DisputeService::class)->resolve($dispute, $admin, ['action' => 'uphold', 'resolution' => 'Provider did not complete the job.']);

        $this->assertSame(DisputeStatus::Upheld, $dispute->fresh()->status);
        $this->assertSame(JobStatus::Cancelled, $job->fresh()->status);
        $this->assertSame(JobPaymentStatus::Reversed, JobPayment::sole()->status);
        $this->assertSame('0.00', app(WalletLedger::class)->availableBalance($provider->fresh()));
    }

    public function test_a_partial_resolution_reduces_the_provider_earning(): void
    {
        [$job, $finder, $provider] = $this->paidJob(releasePayment: true);
        $dispute = app(DisputeService::class)->open($job, $finder, DisputeCategory::ServiceNotAsAgreed, 'work was only half finished here');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        app(DisputeService::class)->resolve($dispute, $admin, ['action' => 'partial', 'refund_amount' => '200.00', 'resolution' => 'Half the bracket work was missing.']);

        $this->assertSame(DisputeStatus::PartiallyUpheld, $dispute->fresh()->status);
        $this->assertSame('480.00', app(WalletLedger::class)->availableBalance($provider->fresh())); // 680 - 200
        $this->assertSame(JobStatus::Completed, $job->fresh()->status);
    }

    public function test_a_resolution_can_open_a_linked_enforcement_case(): void
    {
        [$job, $finder, $provider] = $this->paidJob();
        $dispute = app(DisputeService::class)->open($job, $finder, DisputeCategory::Conduct, 'provider was rude and aggressive');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        app(DisputeService::class)->resolve($dispute, $admin, ['action' => 'uphold', 'resolution' => 'Confirmed.', 'open_enforcement' => true, 'enforce_against' => 'respondent']);

        $case = EnforcementCase::sole();
        $this->assertSame($provider->id, $case->user_id);
        $this->assertSame($case->id, $dispute->fresh()->enforcement_case_id);
    }

    public function test_the_raiser_can_withdraw_an_open_dispute(): void
    {
        [$job, $finder] = $this->paidJob();
        $dispute = app(DisputeService::class)->open($job, $finder, DisputeCategory::Other, 'sorted it out directly now');

        $this->actingAs($finder)->patch(route('disputes.withdraw', $dispute))->assertRedirect();

        $this->assertSame(DisputeStatus::Withdrawn, $dispute->fresh()->status);
        $this->assertSame(JobStatus::Completed, $job->fresh()->status);
    }

    /**
     * @return array{0: Job, 1: User, 2: User}
     */
    private function paidJob(bool $releasePayment = false): array
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
            'agreed_price' => '800.00', 'status' => JobStatus::InProgress, 'accepted_at' => now(), 'started_at' => now(),
        ]);

        app(JobService::class)->transition($job, $provider, JobStatus::Completed, null);
        app(JobPaymentService::class)->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');

        if ($releasePayment) {
            app(JobPaymentService::class)->release($job->jobPayment->fresh(), User::factory()->create(['role' => UserRole::Accounting]));
        }

        return [$job->fresh(), $finder, $provider];
    }
}
