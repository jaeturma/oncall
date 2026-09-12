<?php

namespace Tests\Feature;

use App\Enums\CommissionType;
use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WalletTransactionType;
use App\Models\AccountType;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\JobPaymentService;
use App\Services\JobService;
use App\Services\WalletLedger;
use App\Services\WithdrawalWorkflow;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JobEarningsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private WalletLedger $ledger;

    private JobPaymentService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(WalletLedger::class);
        $this->payments = app(JobPaymentService::class);
    }

    public function test_completing_a_job_opens_a_pending_payment_with_the_global_fee_split(): void
    {
        [$job] = $this->completedJob('1000.00');

        $payment = JobPayment::sole();
        $this->assertSame(JobPaymentStatus::Pending, $payment->status);
        $this->assertSame('1000.00', (string) $payment->gross_amount);
        $this->assertSame('150.00', (string) $payment->platform_fee); // 15% global default
        $this->assertSame('850.00', (string) $payment->net_amount);
        $this->assertSame($job->provider_id, $payment->provider_id);
    }

    public function test_platform_fee_uses_the_account_type_override_when_set(): void
    {
        $type = AccountType::create(['name' => 'Boosted', 'slug' => 'boosted', 'registration_fee' => 0, 'sponsor_commission_type' => CommissionType::None, 'sponsor_commission_value' => 0, 'platform_commission_percent' => 20]);
        [$job] = $this->completedJob('1000.00', providerAccountType: $type);

        $this->assertSame('200.00', (string) JobPayment::sole()->platform_fee);
    }

    public function test_finder_confirms_payment_which_records_a_pending_provider_earning(): void
    {
        [$job, $finder, $provider] = $this->completedJob('1000.00');

        $this->actingAs($finder)->patch(route('job-payments.confirm', $job->jobPayment), [
            'payment_method' => 'GCash', 'payment_reference' => 'ref-1',
        ])->assertRedirect();

        $payment = JobPayment::sole();
        $this->assertSame(JobPaymentStatus::Paid, $payment->status);
        $this->assertSame('850.00', $this->ledger->pendingBalance($provider));
        $this->assertSame('0.00', $this->ledger->availableBalance($provider));
    }

    public function test_accounting_release_moves_the_earning_into_the_provider_wallet(): void
    {
        [$job, $finder, $provider] = $this->completedJob('1000.00');
        $this->payments->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');

        $accounting = User::factory()->create(['role' => UserRole::Accounting, 'status' => UserStatus::Active]);
        $this->actingAs($accounting)->patch(route('staff.job-payments.update', JobPayment::sole()), ['decision' => 'release'])->assertRedirect();

        $this->assertSame(JobPaymentStatus::Released, JobPayment::sole()->status);
        $this->assertSame('850.00', $this->ledger->availableBalance($provider));
    }

    public function test_released_earning_can_then_be_withdrawn(): void
    {
        [$job, $finder, $provider] = $this->completedJob('1000.00');
        $this->payments->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');
        $this->payments->release($job->jobPayment->fresh(), User::factory()->create(['role' => UserRole::Accounting]));

        app(WithdrawalWorkflow::class)->request($provider->fresh(), '800.00', 'GCash', 'ref');

        $this->assertSame('50.00', $this->ledger->availableBalance($provider->fresh()));
    }

    public function test_only_the_service_finder_can_confirm_payment(): void
    {
        [$job, , $provider] = $this->completedJob('1000.00');

        $this->actingAs($provider)->patch(route('job-payments.confirm', $job->jobPayment), ['payment_method' => 'Cash', 'payment_reference' => 'x'])->assertForbidden();
        $this->actingAs(User::factory()->create())->patch(route('job-payments.confirm', $job->jobPayment), ['payment_method' => 'Cash', 'payment_reference' => 'x'])->assertForbidden();
    }

    public function test_only_accounting_or_admin_can_release_or_reverse(): void
    {
        [$job, $finder, $provider] = $this->completedJob('1000.00');
        $this->payments->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');

        $this->actingAs($provider)->patch(route('staff.job-payments.update', JobPayment::sole()), ['decision' => 'release'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Cashier]))->patch(route('staff.job-payments.update', JobPayment::sole()), ['decision' => 'release'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Budget]))->patch(route('staff.job-payments.update', JobPayment::sole()), ['decision' => 'release'])->assertForbidden();
    }

    public function test_reversing_a_released_earning_removes_it_from_the_balance(): void
    {
        [$job, $finder, $provider] = $this->completedJob('1000.00');
        $this->payments->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref');
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $this->payments->release($job->jobPayment->fresh(), $accounting);

        $this->payments->reverse(JobPayment::sole(), $accounting, 'chargeback');

        $this->assertSame(JobPaymentStatus::Reversed, JobPayment::sole()->status);
        $this->assertSame('0.00', $this->ledger->availableBalance($provider->fresh()));
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $provider->id, 'type' => WalletTransactionType::Reversal->value]);
    }

    /**
     * @return array{0: Job, 1: User, 2: User}
     */
    private function completedJob(string $agreedPrice, ?AccountType $providerAccountType = null): array
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active, 'account_type_id' => $providerAccountType?->id]);
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
