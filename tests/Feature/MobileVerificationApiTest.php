<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OtpCode;
use App\Models\SmsProvider;
use App\Models\SmsSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileVerificationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_request_normalizes_the_number_and_never_returns_the_otp_in_production(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'abc123'], 200)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'phone' => null]);
        Sanctum::actingAs($user);
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->postJson('/api/v1/mobile-verification/request', ['mobile' => '09171234567']);

        $response->assertOk()->assertJsonPath('mobile', '+639171234567');
        $response->assertJsonPath('demo_code', null);
        $body = json_encode($response->json());
        $otp = OtpCode::sole();
        $this->assertStringNotContainsString($otp->otp_hash, $body);
    }

    public function test_verify_never_reveals_which_part_of_the_code_was_wrong(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'phone' => '+639171234567']);
        Sanctum::actingAs($user);

        $expired = $this->postJson('/api/v1/mobile-verification/verify', ['code' => '000000'])->json('message');
        $this->assertSame('Invalid or expired verification code.', $expired);
    }

    public function test_full_request_and_verify_cycle_marks_the_mobile_verified(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'phone' => null]);
        Sanctum::actingAs($user);

        $requestResponse = $this->postJson('/api/v1/mobile-verification/request', ['mobile' => '09171234567']);
        $requestResponse->assertOk();
        $code = $requestResponse->json('demo_code');

        $this->postJson('/api/v1/mobile-verification/verify', ['code' => $code])
            ->assertOk()
            ->assertJson(['mobile_verified' => true]);

        $this->assertNotNull($user->refresh()->phone_verified_at);
    }

    public function test_resend_reuses_the_pending_mobile_number(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'phone' => null]);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/mobile-verification/request', ['mobile' => '09171234567'])->assertOk();
        OtpCode::query()->update(['sent_at' => now()->subHour()]);

        $this->postJson('/api/v1/mobile-verification/resend')->assertOk()->assertJsonPath('mobile', '+639171234567');
    }

    public function test_resend_without_a_prior_request_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'phone' => null]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/mobile-verification/resend')->assertStatus(422);
    }

    public function test_repeated_requests_are_rate_limited_with_a_retry_after(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'phone' => null]);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/mobile-verification/request', ['mobile' => '09171234567'])->assertOk();

        $response = $this->postJson('/api/v1/mobile-verification/request', ['mobile' => '09171234567']);

        $response->assertStatus(429)->assertJsonStructure(['message', 'retry_after']);
        $this->assertGreaterThan(0, $response->json('retry_after'));
    }

    public function test_a_back_office_token_cannot_reach_mobile_verification_endpoints(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/mobile-verification/request', ['mobile' => '09171234567'])
            ->assertForbidden();
    }

    public function test_admin_sms_configuration_routes_do_not_exist_on_the_mobile_api(): void
    {
        $this->assertFalse(Route::has('api.settings.sms.edit'));
        $this->assertFalse(Route::has('api.sms-logs.index'));
    }
}
