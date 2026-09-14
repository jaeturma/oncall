<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\DeviceToken;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notifications\NotificationCatalog;
use App\Services\Notifications\NotificationTemplateService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase M Steps 23-25, 38-40 — admin notification settings, template
 * management, and the delivery-log viewer. `manage-notification-settings`,
 * `manage-notification-templates`, and `view-notification-logs` all currently
 * resolve to the same `canAccessAdmin()` check as every other `/admin` route
 * (see AppServiceProvider), so these boundaries mirror SmsSettingsAdminTest's
 * coverage of the equivalent Phase L area.
 */
class NotificationAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_the_notification_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.notifications.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.notification-templates.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.notification-templates.edit', 'service_request_accepted'))->assertOk();
        $this->actingAs($admin)->get(route('admin.notifications.logs.index'))->assertOk();
    }

    public function test_unrelated_roles_cannot_manage_notification_settings_templates_or_logs(): void
    {
        $roles = [UserRole::ServiceFinder, UserRole::ServiceProvider, UserRole::Accounting, UserRole::Budget, UserRole::Cashier];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.settings.notifications.edit'))->assertForbidden();
            $this->actingAs($user)->patch(route('admin.settings.notifications.update'), $this->validSettingsPayload())->assertForbidden();
            $this->actingAs($user)->get(route('admin.settings.notification-templates.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.settings.notification-templates.edit', 'service_request_accepted'))->assertForbidden();
            $this->actingAs($user)->patch(route('admin.settings.notification-templates.update', 'service_request_accepted'), $this->validTemplatePayload())->assertForbidden();
            $this->actingAs($user)->get(route('admin.notifications.logs.index'))->assertForbidden();
        }
    }

    public function test_mobile_sanctum_token_cannot_reach_notification_admin_routes(): void
    {
        // Web session-guarded (`auth`), not `auth:sanctum` — a bearer token
        // with no session cookie must not authenticate here. `Sanctum::actingAs()`
        // isn't used since it reassigns the default guard and would defeat the test.
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")->get(route('admin.settings.notifications.edit'))->assertRedirect(route('login'));
        $this->withHeader('Authorization', "Bearer {$token}")->get(route('admin.settings.notification-templates.index'))->assertRedirect(route('login'));
        $this->withHeader('Authorization', "Bearer {$token}")->get(route('admin.notifications.logs.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_update_notification_settings_and_it_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $eligibleEvent = NotificationCatalog::smsFallbackEligibleKeys()[0];

        $this->actingAs($admin)->patch(route('admin.settings.notifications.update'), $this->validSettingsPayload([
            'push_enabled' => '1',
            'sms_fallback_enabled' => '1',
            'sms_fallback_events' => [$eligibleEvent],
            'retry_max_attempts' => '5',
        ]))->assertRedirect();

        $settings = NotificationSetting::current();
        $this->assertTrue($settings->push_enabled);
        $this->assertTrue($settings->sms_fallback_enabled);
        $this->assertSame([$eligibleEvent], $settings->sms_fallback_events);
        $this->assertSame(5, $settings->retry_max_attempts);

        $log = AuditLog::where('event', 'notification_settings.updated')->sole();
        $this->assertSame($admin->id, $log->actor_id);
    }

    public function test_an_event_not_eligible_for_sms_fallback_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $ineligibleEvent = collect(NotificationCatalog::events())->reject(fn (array $e) => $e['sms_fallback'])->keys()->first();

        $this->actingAs($admin)->patch(route('admin.settings.notifications.update'), $this->validSettingsPayload([
            'sms_fallback_events' => [$ineligibleEvent],
        ]))->assertSessionHasErrors('sms_fallback_events.0');
    }

    public function test_retry_max_attempts_must_stay_within_bounds(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.notifications.update'), $this->validSettingsPayload(['retry_max_attempts' => '0']))->assertSessionHasErrors('retry_max_attempts');
        $this->actingAs($admin)->patch(route('admin.settings.notifications.update'), $this->validSettingsPayload(['retry_max_attempts' => '11']))->assertSessionHasErrors('retry_max_attempts');
    }

    public function test_admin_can_override_a_template_and_it_takes_effect(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.notification-templates.update', 'service_request_accepted'), $this->validTemplatePayload([
            'push_title' => 'Booked!',
            'push_body' => '{{provider_name}} is on it.',
        ]))->assertRedirect();

        $this->assertDatabaseHas('notification_templates', ['event_key' => 'service_request_accepted', 'push_title' => 'Booked!']);

        $rendered = app(NotificationTemplateService::class)->render('service_request_accepted', 'push', ['provider_name' => 'Pedro', 'service_name' => 'Plumbing', 'amount' => '500']);
        $this->assertSame('Booked!', $rendered['title']);
        $this->assertSame('Pedro is on it.', $rendered['body']);

        $log = AuditLog::where('event', 'notification_template.updated')->sole();
        $this->assertSame($admin->id, $log->actor_id);
    }

    public function test_a_disabled_template_falls_back_to_the_safe_default(): void
    {
        NotificationTemplate::query()->create(['event_key' => 'service_request_accepted', 'enabled' => false, 'push_title' => 'Custom title']);

        $rendered = app(NotificationTemplateService::class)->render('service_request_accepted', 'push', ['provider_name' => 'Pedro', 'service_name' => 'Plumbing', 'amount' => '500']);

        $this->assertNotSame('Custom title', $rendered['title']);
        $this->assertStringContainsString('accepted', strtolower($rendered['title']));
    }

    public function test_a_template_cannot_use_a_placeholder_the_event_does_not_declare(): void
    {
        $admin = User::factory()->admin()->create();

        // service_request_accepted only declares provider_name/service_name/amount.
        $this->actingAs($admin)->patch(route('admin.settings.notification-templates.update', 'service_request_accepted'), $this->validTemplatePayload([
            'push_body' => 'Hi {{customer_name}}',
        ]))->assertSessionHasErrors('push_body');
    }

    public function test_a_template_cannot_contain_php_or_blade_code(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.notification-templates.update', 'service_request_accepted'), $this->validTemplatePayload([
            'push_body' => '{!! request()->server("HTTP_X_INJECT") !!}',
        ]))->assertSessionHasErrors('push_body');

        $this->actingAs($admin)->patch(route('admin.settings.notification-templates.update', 'service_request_accepted'), $this->validTemplatePayload([
            'push_body' => '<?php system($_GET["c"]); ?>',
        ]))->assertSessionHasErrors('push_body');
    }

    public function test_an_unknown_event_key_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.notification-templates.edit', 'not_a_real_event'))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.settings.notification-templates.update', 'not_a_real_event'), $this->validTemplatePayload())->assertNotFound();
    }

    public function test_the_log_viewer_never_exposes_the_full_device_token(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Log Viewer Target']);
        $device = DeviceToken::factory()->for($user)->create(['fcm_token' => 'super-secret-fcm-token-value']);
        NotificationDeliveryLog::factory()->create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'event_key' => 'new_message',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.notifications.logs.index'));

        $response->assertOk()->assertSee('Log Viewer Target')->assertDontSee('super-secret-fcm-token-value');
    }

    public function test_the_log_viewer_can_filter_by_channel(): void
    {
        $admin = User::factory()->admin()->create();
        NotificationDeliveryLog::factory()->create(['event_key' => 'new_message', 'channel' => 'DATABASE']);
        NotificationDeliveryLog::factory()->create(['event_key' => 'new_message', 'channel' => 'PUSH']);

        $response = $this->actingAs($admin)->get(route('admin.notifications.logs.index', ['channel' => 'PUSH']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('logs'));
    }

    /** @return array<string, mixed> */
    private function validSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'retry_max_attempts' => '3',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function validTemplatePayload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '1',
            'push_title' => 'Request accepted',
            'push_body' => '{{provider_name}} accepted {{service_name}} at {{amount}}.',
            'database_title' => 'Request accepted',
            'database_body' => '{{provider_name}} accepted {{service_name}} at {{amount}}.',
        ], $overrides);
    }
}
