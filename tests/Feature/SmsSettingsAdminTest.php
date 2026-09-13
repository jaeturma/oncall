<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\SmsProvider;
use App\Models\SmsSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsSettingsAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_the_sms_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.sms.edit'))->assertOk();
    }

    public function test_unrelated_roles_cannot_manage_sms_settings(): void
    {
        $roles = [UserRole::ServiceFinder, UserRole::ServiceProvider, UserRole::Accounting, UserRole::Budget, UserRole::Cashier];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.settings.sms.edit'))->assertForbidden();
            $this->actingAs($user)->patch(route('admin.settings.sms.update'), $this->validPayload())->assertForbidden();
            $this->actingAs($user)->get(route('admin.sms-logs.index'))->assertForbidden();
        }
    }

    public function test_mobile_sanctum_token_cannot_reach_sms_admin_routes(): void
    {
        // These are web session-guarded routes (`auth`, not `auth:sanctum`),
        // so a bearer token alone — with no session cookie — must not
        // authenticate them. `Sanctum::actingAs()` isn't used here: it
        // reassigns the app's *default* auth guard, which would make the
        // plain `auth` middleware pass too and defeat the point of this test.
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")->get(route('admin.settings.sms.edit'))->assertRedirect(route('login'));
        $this->withHeader('Authorization', "Bearer {$token}")->patch(route('admin.settings.sms.update'), $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_admin_can_save_settings_and_the_credential_is_encrypted_at_rest(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['credential' => 'super-secret-token-XYZ']))->assertRedirect();

        $settings = SmsSetting::current();
        $this->assertTrue($settings->enabled);
        $provider = $settings->activeProvider;
        $this->assertSame('super-secret-token-XYZ', $provider->config['credential']);

        // The raw database column must never contain the plaintext secret.
        $rawConfig = DB::table('sms_providers')->where('id', $provider->id)->value('config');
        $this->assertStringNotContainsString('super-secret-token-XYZ', $rawConfig);
    }

    public function test_leaving_the_credential_blank_preserves_the_existing_value(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['credential' => 'original-secret']));

        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['credential' => '']))->assertRedirect();

        $this->assertSame('original-secret', SmsSetting::current()->activeProvider->config['credential']);
    }

    public function test_the_edit_page_never_renders_the_full_credential(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['credential' => 'super-secret-token-XYZ']));

        $response = $this->actingAs($admin)->get(route('admin.settings.sms.edit'));

        $response->assertOk()->assertDontSee('super-secret-token-XYZ');
    }

    public function test_updating_settings_writes_an_audit_log_without_the_credential(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['credential' => 'super-secret-token-XYZ']));

        $log = AuditLog::where('event', 'sms_settings.updated')->sole();
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertStringNotContainsString('super-secret-token-XYZ', json_encode($log->before_json));
        $this->assertStringNotContainsString('super-secret-token-XYZ', json_encode($log->after_json));
    }

    public function test_an_unsafe_provider_url_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['base_url' => 'http://169.254.169.254/latest/meta-data']))->assertSessionHasErrors('base_url');
    }

    public function test_an_unsafe_message_template_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['otp_message_template' => 'Your code is {{otp}}. {{ App::make("something")->dangerous() }}']))->assertSessionHasErrors('otp_message_template');
    }

    public function test_an_unsafe_otp_policy_value_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['otp_resend_cooldown_seconds' => 0]))->assertSessionHasErrors('otp_resend_cooldown_seconds');
        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['otp_max_attempts' => 999]))->assertSessionHasErrors('otp_max_attempts');
        $this->actingAs($admin)->patch(route('admin.settings.sms.update'), $this->validPayload(['otp_expiry_minutes' => 1440]))->assertSessionHasErrors('otp_expiry_minutes');
    }

    public function test_test_sms_is_audited_and_never_exposes_the_raw_provider_response(): void
    {
        Http::fake(['sms.example.com/*' => Http::response('{"secret_internal_field": "leak-me-not"}', 502)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.settings.sms.test'), ['mobile' => '09171234567']);

        $response->assertRedirect()->assertSessionHasErrors('mobile');
        $response->assertDontSee('leak-me-not');
        $this->assertDatabaseHas('audit_logs', ['event' => 'sms_settings.test_sent', 'actor_id' => $admin->id]);
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '1',
            'driver' => 'GENERIC_HTTP',
            'provider_name' => 'Test Gateway',
            'base_url' => 'https://sms.example.com/send',
            'auth_type' => 'BEARER_TOKEN',
            'auth_param_name' => null,
            'username' => null,
            'credential' => 'a-secret',
            'sender_id' => 'ONCALL',
            'default_country_code' => '63',
            'test_mobile_number' => '09171234567',
            'otp_length' => 6,
            'otp_expiry_minutes' => 5,
            'otp_resend_cooldown_seconds' => 60,
            'otp_max_attempts' => 5,
            'otp_max_sends_per_mobile_per_hour' => 5,
            'otp_max_sends_per_ip_per_hour' => 20,
            'otp_message_template' => 'Your Oncall Philippines verification code is {{otp}}. It expires in {{minutes}} minutes.',
        ], $overrides);
    }
}
