<?php

namespace Tests\Feature;

use App\Enums\DisputeCategory;
use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\RefundStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\Municipality;
use App\Models\PaymentSetting;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Refund;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DisputeService;
use App\Services\JobPaymentService;
use App\Services\JobService;
use App\Services\RefundService;
use App\Services\WalletLedger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_request_a_refund_on_a_released_payment(): void
    {
        [$job, $finder] = $this->releasedJob();

        $this->actingAs($finder)->postJson("/api/v1/payments/{$job->jobPayment->id}/refund-requests", [
            'amount' => '200.00',
            'reason' => 'Only part of the work was completed.',
        ])->assertCreated();

        $refund = Refund::sole();
        $this->assertSame(RefundStatus::Requested, $refund->status);
        $this->assertSame('200.00', (string) $refund->amount);
        $this->assertSame($finder->id, $refund->requested_by);
    }

    public function test_refund_amount_cannot_exceed_refundable_amount(): void
    {
        [$job, $finder] = $this->releasedJob();

        $this->expectExceptionMessage('exceeds what remains refundable');
        app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '999999.00', 'too much');
    }

    public function test_cannot_request_refund_while_a_dispute_is_open(): void
    {
        [$job, $finder] = $this->releasedJob();
        app(DisputeService::class)->open($job, $finder, DisputeCategory::PaymentIssue, 'payment amount is wrong here');

        $this->expectExceptionMessage('open dispute');
        app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '100.00', 'while disputed');
    }

    public function test_a_stranger_cannot_request_a_refund(): void
    {
        [$job] = $this->releasedJob();
        $stranger = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);

        $this->actingAs($stranger)->postJson("/api/v1/payments/{$job->jobPayment->id}/refund-requests", [
            'amount' => '100.00',
            'reason' => 'not my job',
        ])->assertForbidden();
    }

    public function test_admin_can_approve_a_refund_which_posts_to_the_ledger_and_reduces_the_balance(): void
    {
        [$job, $finder, $provider] = $this->releasedJob();
        $this->assertSame('850.00', app(WalletLedger::class)->availableBalance($provider));
        $refund = app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '200.00', 'partial dissatisfaction');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('staff.refunds.update', $refund), ['decision' => 'approve'])->assertRedirect();

        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
        $this->assertSame('650.00', app(WalletLedger::class)->availableBalance($provider->fresh()));
        $this->assertSame('200.00', (string) JobPayment::sole()->refunded_amount);
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $provider->id, 'type' => 'REFUND']);
    }

    public function test_staff_can_reject_a_refund_with_a_reason(): void
    {
        [$job, $finder] = $this->releasedJob();
        $refund = app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '100.00', 'changed my mind');
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);

        $this->actingAs($accounting)->patch(route('staff.refunds.update', $refund), ['decision' => 'reject', 'notes' => 'Outside policy.'])->assertRedirect();

        $this->assertSame(RefundStatus::Rejected, $refund->fresh()->status);
        $this->assertSame('Outside policy.', $refund->fresh()->decision_notes);
    }

    public function test_marketplace_role_cannot_approve_or_reject_a_refund(): void
    {
        [$job, $finder, $provider] = $this->releasedJob();
        $refund = app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '100.00', 'reason');

        $this->actingAs($provider)->patch(route('staff.refunds.update', $refund), ['decision' => 'approve'])->assertForbidden();
    }

    public function test_refund_window_is_enforced(): void
    {
        [$job, $finder] = $this->releasedJob();
        $job->jobPayment->update(['confirmed_at' => now()->subDays(31)]);
        PaymentSetting::current()->update(['refund_window_days' => 30]);

        $this->expectExceptionMessage('outside the refund window');
        app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '100.00', 'too late');
    }

    public function test_max_refund_requests_per_payment_is_enforced(): void
    {
        [$job, $finder] = $this->releasedJob();
        PaymentSetting::current()->update(['max_refund_requests_per_payment' => 1]);
        app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '50.00', 'first request');

        $this->expectExceptionMessage('maximum number of refund requests');
        app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '50.00', 'second request');
    }

    public function test_the_only_allowed_refund_request_can_still_be_approved(): void
    {
        // Regression: the eligibility re-check inside approve() must not
        // count the refund being approved against its own request-limit slot.
        [$job, $finder] = $this->releasedJob();
        PaymentSetting::current()->update(['max_refund_requests_per_payment' => 1]);
        $refund = app(RefundService::class)->request($job->jobPayment->fresh(), $finder, '50.00', 'only request');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        app(RefundService::class)->approve($refund, $admin);

        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
    }

    public function test_a_second_partial_refund_only_reduces_the_remaining_refundable_amount(): void
    {
        [$job, $finder, $provider] = $this->releasedJob();
        $refunds = app(RefundService::class);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $first = $refunds->request($job->jobPayment->fresh(), $finder, '300.00', 'first partial');
        $refunds->approve($first, $admin);

        $this->assertSame('550.00', $job->jobPayment->fresh()->refundableAmount());

        $second = $refunds->request($job->jobPayment->fresh(), $finder, '550.00', 'remaining refund');
        $refunds->approve($second, $admin);

        $this->assertSame('0.00', $job->jobPayment->fresh()->refundableAmount());
        $this->assertSame('0.00', app(WalletLedger::class)->availableBalance($provider->fresh()));
    }

    /**
     * @return array{0: Job, 1: User, 2: User}
     */
    private function releasedJob(): array
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
        app(JobPaymentService::class)->release($job->jobPayment->fresh(), User::factory()->create(['role' => UserRole::Accounting]));

        $this->assertSame(JobPaymentStatus::Released, $job->fresh()->jobPayment->status);

        return [$job->fresh(), $finder, $provider];
    }
}
