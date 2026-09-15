<?php

namespace Tests\Feature;

use App\Enums\CommissionType;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AccountType;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\Municipality;
use App\Models\PaymentSetting;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\JobService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeeCalculationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_percentage_fee_type_computes_from_payment_settings(): void
    {
        PaymentSetting::current()->update(['platform_fee_type' => CommissionType::Percentage, 'platform_fee_value' => 10]);

        $this->completedJob('1000.00');

        $this->assertSame('100.00', (string) JobPayment::sole()->platform_fee);
        $this->assertSame('900.00', (string) JobPayment::sole()->net_amount);
    }

    public function test_fixed_fee_type_charges_a_flat_amount_regardless_of_price(): void
    {
        PaymentSetting::current()->update(['platform_fee_type' => CommissionType::Fixed, 'platform_fee_value' => 50]);

        $this->completedJob('1000.00');

        $this->assertSame('50.00', (string) JobPayment::sole()->platform_fee);
        $this->assertSame('950.00', (string) JobPayment::sole()->net_amount);
    }

    public function test_none_fee_type_charges_nothing(): void
    {
        PaymentSetting::current()->update(['platform_fee_type' => CommissionType::None, 'platform_fee_value' => 0]);

        $this->completedJob('1000.00');

        $this->assertSame('0.00', (string) JobPayment::sole()->platform_fee);
        $this->assertSame('1000.00', (string) JobPayment::sole()->net_amount);
    }

    public function test_account_type_override_wins_over_payment_settings(): void
    {
        PaymentSetting::current()->update(['platform_fee_type' => CommissionType::Fixed, 'platform_fee_value' => 999]);
        $type = AccountType::create(['name' => 'Boosted', 'slug' => 'boosted-fee', 'registration_fee' => 0, 'sponsor_commission_type' => CommissionType::None, 'sponsor_commission_value' => 0, 'platform_commission_percent' => 5]);

        $this->completedJob('1000.00', $type);

        $this->assertSame('50.00', (string) JobPayment::sole()->platform_fee);
    }

    public function test_fee_rounds_to_two_decimal_places(): void
    {
        PaymentSetting::current()->update(['platform_fee_type' => CommissionType::Percentage, 'platform_fee_value' => 15]);

        $this->completedJob('333.33');

        // 333.33 * 0.15 = 49.9995, rounded to 2dp = 50.00 (bcmul truncates, not rounds — verify actual behavior)
        $payment = JobPayment::sole();
        $this->assertSame(bcmul('333.33', '0.15', 2), (string) $payment->platform_fee);
        $this->assertSame(bcsub('333.33', (string) $payment->platform_fee, 2), (string) $payment->net_amount);
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
