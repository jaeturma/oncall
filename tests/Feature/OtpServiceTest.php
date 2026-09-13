<?php

namespace Tests\Feature;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\SmsProvider;
use App\Models\SmsSetting;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_request_stores_a_hash_never_the_plaintext_code(): void
    {
        $user = User::factory()->create();

        $result = $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $otp = OtpCode::sole();
        $this->assertNotNull($result->plainCode);
        $this->assertNotSame($result->plainCode, $otp->otp_hash);
        $this->assertTrue(Hash::check($result->plainCode, $otp->otp_hash));
        $this->assertSame('+639171234567', $otp->mobile);
    }

    public function test_a_valid_code_verifies_and_a_used_code_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $result = $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $this->assertTrue($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, $result->plainCode));
        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, $result->plainCode));
    }

    public function test_an_invalid_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, '000000'));
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $result = $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');
        OtpCode::sole()->update(['expires_at' => now()->subMinute()]);

        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, $result->plainCode));
    }

    public function test_requesting_a_new_code_invalidates_the_previous_one(): void
    {
        $user = User::factory()->create();
        $first = $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');
        OtpCode::sole()->update(['sent_at' => now()->subHour()]); // clear cooldown for the second request

        $second = $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, $first->plainCode));
        $this->assertTrue($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, $second->plainCode));
    }

    public function test_a_code_issued_for_one_purpose_cannot_verify_another(): void
    {
        $user = User::factory()->create();
        $result = $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        // No second purpose exists yet in this app, so simulate one directly
        // at the database level (bypassing the enum cast) to prove the query
        // is purpose-scoped, not just "any code for this user+mobile".
        DB::table('otp_codes')->update(['purpose' => 'SOME_OTHER_PURPOSE']);

        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, $result->plainCode));
    }

    public function test_attempt_limit_invalidates_the_code(): void
    {
        SmsSetting::current()->update(['otp_max_attempts' => 3]);
        $user = User::factory()->create();
        $result = $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, '000000'));
        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, '000000'));
        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, '000000'));

        // Attempts are exhausted — even the correct code no longer verifies.
        $this->assertFalse($this->service()->verify($user, '09171234567', OtpPurpose::MobileVerification, $result->plainCode));
    }

    public function test_resend_cooldown_is_enforced(): void
    {
        $user = User::factory()->create();
        $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $this->expectException(TooManyRequestsHttpException::class);
        $this->service()->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');
    }

    public function test_per_mobile_hourly_cap_is_enforced(): void
    {
        SmsSetting::current()->update(['otp_max_sends_per_mobile_per_hour' => 2, 'otp_resend_cooldown_seconds' => 30]);
        $user = User::factory()->create();
        $service = $this->service();

        $service->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');
        OtpCode::query()->update(['sent_at' => now()->subMinute()]);
        $service->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.2');
        OtpCode::query()->update(['sent_at' => now()->subMinute()]);

        $this->expectException(TooManyRequestsHttpException::class);
        $service->request($user, '09171234567', OtpPurpose::MobileVerification, '203.0.113.3');
    }

    public function test_per_ip_hourly_cap_is_enforced(): void
    {
        SmsSetting::current()->update(['otp_max_sends_per_ip_per_hour' => 2, 'otp_resend_cooldown_seconds' => 30]);
        $service = $this->service();

        $service->request(User::factory()->create(), '09171234567', OtpPurpose::MobileVerification, '203.0.113.9');
        $service->request(User::factory()->create(), '09181234567', OtpPurpose::MobileVerification, '203.0.113.9');

        $this->expectException(TooManyRequestsHttpException::class);
        $service->request(User::factory()->create(), '09191234567', OtpPurpose::MobileVerification, '203.0.113.9');
    }

    public function test_request_sends_through_the_active_provider_when_sms_is_enabled(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'abc123'], 200)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);

        $result = $this->service()->request(User::factory()->create(), '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $this->assertTrue($result->sent);
        $this->assertDatabaseHas('sms_delivery_logs', ['status' => 'SENT', 'provider_message_id' => 'abc123']);
    }

    public function test_delivery_log_never_contains_the_message_body_or_credential(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'abc123'], 200)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);

        $this->service()->request(User::factory()->create(), '09171234567', OtpPurpose::MobileVerification, '203.0.113.1');

        $columns = Schema::getColumnListing('sms_delivery_logs');
        $this->assertNotContains('body', $columns);
        $this->assertNotContains('message', $columns);
        $this->assertNotContains('credential', $columns);
    }

    private function service(): OtpService
    {
        return app(OtpService::class);
    }
}
