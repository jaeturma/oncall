<?php

namespace Tests\Feature;

use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\Municipality;
use App\Models\PaymentAttempt;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\JobPaymentService;
use App\Services\JobService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentAttemptTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_confirming_payment_creates_a_verified_attempt(): void
    {
        [$job, $finder] = $this->completedJob();

        app(JobPaymentService::class)->confirmPaid($job->jobPayment, $finder, 'GCash', 'ref-1');

        $attempt = PaymentAttempt::sole();
        $this->assertSame(PaymentAttemptStatus::Verified, $attempt->status);
        $this->assertSame('GCash', $attempt->method);
        $this->assertSame('MANUAL', $attempt->gateway);
        $this->assertSame($finder->id, $attempt->submitted_by);
    }

    public function test_a_reversed_payment_can_be_retried_with_a_new_attempt_without_discarding_the_old_one(): void
    {
        [$job, $finder] = $this->completedJob();
        $payments = app(JobPaymentService::class);
        $payments->confirmPaid($job->jobPayment, $finder, 'GCash', 'first-ref');
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $payments->reverse(JobPayment::sole(), $accounting, 'chargeback');

        $payments->confirmPaid(JobPayment::sole()->fresh(), $finder, 'Cash', 'second-ref');

        $this->assertSame(2, PaymentAttempt::count());
        $this->assertSame(JobPaymentStatus::Paid, JobPayment::sole()->status);
        $this->assertSame('second-ref', JobPayment::sole()->payment_reference);
    }

    public function test_receipt_number_is_generated_once_and_stable_across_retries(): void
    {
        [$job, $finder] = $this->completedJob();
        $payments = app(JobPaymentService::class);
        $payments->confirmPaid($job->jobPayment, $finder, 'GCash', 'first-ref');
        $firstReceipt = JobPayment::sole()->receipt_number;
        $this->assertNotNull($firstReceipt);

        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $payments->reverse(JobPayment::sole(), $accounting, 'chargeback');
        $payments->confirmPaid(JobPayment::sole()->fresh(), $finder, 'Cash', 'second-ref');

        $this->assertSame($firstReceipt, JobPayment::sole()->receipt_number);
    }

    public function test_a_paid_payment_cannot_be_confirmed_again(): void
    {
        [$job, $finder] = $this->completedJob();
        $payments = app(JobPaymentService::class);
        $payments->confirmPaid($job->jobPayment, $finder, 'GCash', 'first-ref');

        $this->expectExceptionMessage('already been confirmed');
        $payments->confirmPaid(JobPayment::sole()->fresh(), $finder, 'Cash', 'second-ref');
    }

    /**
     * @return array{0: Job, 1: User, 2: User}
     */
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
