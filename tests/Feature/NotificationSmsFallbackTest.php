<?php

namespace Tests\Feature;

use App\Enums\DevicePlatform;
use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\PushFailureType;
use App\Jobs\SendPushNotificationJob;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationSetting;
use App\Models\SmsDeliveryLog;
use App\Models\SmsProvider;
use App\Models\SmsSetting;
use App\Models\User;
use App\Services\Notifications\DeviceTokenService;
use App\Services\Notifications\Fcm\FcmAccessTokenProvider;
use App\Services\Notifications\Fcm\FcmClient;
use App\Services\Notifications\Fcm\FcmSendResult;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\PushNotificationService;
use App\Services\Notifications\RetryablePushException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Phase M Step 33 — SMS fallback through Phase L's SmsManager for a narrow,
 * admin-configured set of critical events. Never a generic "push failed, so
 * text them" trigger: NotificationDispatcher fires the SMS attempt
 * unconditionally alongside the push job (it can't wait on an async job's
 * outcome), gated only by catalog eligibility + admin opt-in + verified phone
 * + preference — never by whether the push itself later succeeds or fails.
 */
class NotificationSmsFallbackTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sms_fallback_fires_for_an_admin_enabled_critical_event_through_sms_manager(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'sms-msg-1'], 200)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);
        NotificationSetting::current()->update(['sms_fallback_enabled' => true, 'sms_fallback_events' => ['withdrawal_disbursed']]);
        $recipient = User::factory()->create(['phone' => '+639171234567', 'phone_verified_at' => now()]);

        app(NotificationDispatcher::class)->dispatch($recipient, 'withdrawal_disbursed', ['amount' => '1500.00'], ['screen' => 'withdrawal']);

        // Proves the fallback actually went through Phase L's real send path,
        // not a Phase-M-local reimplementation of provider HTTP logic.
        Http::assertSent(fn ($request): bool => $request->url() === 'https://sms.example.com/send' && str_contains($request['message'] ?? '', '1500.00'));
        $this->assertDatabaseHas('sms_delivery_logs', ['user_id' => $recipient->id, 'mobile' => '+639171234567']);
        $this->assertDatabaseHas('notification_delivery_logs', [
            'user_id' => $recipient->id,
            'event_key' => 'withdrawal_disbursed',
            'channel' => NotificationChannel::Sms->value,
            'status' => NotificationDeliveryStatus::Sent->value,
        ]);
    }

    public function test_sms_fallback_does_not_fire_when_the_admin_has_not_enabled_it_for_this_event(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'sms-msg-1'], 200)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);
        // sms_fallback_enabled left false (the default) — the event is catalog-eligible but not admin-opted-in.
        $recipient = User::factory()->create(['phone' => '+639171234567', 'phone_verified_at' => now()]);

        app(NotificationDispatcher::class)->dispatch($recipient, 'withdrawal_disbursed', ['amount' => '1500.00'], ['screen' => 'withdrawal']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_delivery_logs', 0);
    }

    public function test_sms_fallback_does_not_fire_for_an_event_the_catalog_does_not_mark_eligible(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'sms-msg-1'], 200)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);
        // A stale/tampered settings row naming a non-eligible event —
        // NotificationSetting::enabledSmsFallbackEvents() must still filter it out.
        NotificationSetting::current()->update(['sms_fallback_enabled' => true, 'sms_fallback_events' => ['service_request_accepted']]);
        $recipient = User::factory()->create(['phone' => '+639171234567', 'phone_verified_at' => now()]);

        app(NotificationDispatcher::class)->dispatch($recipient, 'service_request_accepted', ['provider_name' => 'Pedro', 'service_name' => 'Plumbing', 'amount' => '500'], null);

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_delivery_logs', 0);
    }

    public function test_sms_fallback_requires_a_verified_phone_number(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'sms-msg-1'], 200)]);
        $provider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);
        NotificationSetting::current()->update(['sms_fallback_enabled' => true, 'sms_fallback_events' => ['withdrawal_disbursed']]);

        $noPhone = User::factory()->create(['phone' => null, 'phone_verified_at' => null]);
        app(NotificationDispatcher::class)->dispatch($noPhone, 'withdrawal_disbursed', ['amount' => '1500.00'], null);

        $unverified = User::factory()->create(['phone' => '+639171234567', 'phone_verified_at' => null]);
        app(NotificationDispatcher::class)->dispatch($unverified, 'withdrawal_disbursed', ['amount' => '1500.00'], null);

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_delivery_logs', 0);
    }

    public function test_a_transient_push_failure_does_not_trigger_sms_for_a_non_eligible_event(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['message_id' => 'sms-msg-1'], 200)]);
        $smsProvider = SmsProvider::factory()->create(['is_active' => true]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $smsProvider->id]);
        // SMS fallback is fully enabled admin-wide, but 'new_message' is not catalog-eligible for it.
        NotificationSetting::current()->update(['push_enabled' => true, 'sms_fallback_enabled' => true, 'sms_fallback_events' => []]);
        $recipient = User::factory()->create(['phone' => '+639171234567', 'phone_verified_at' => now()]);
        app(DeviceTokenService::class)->register($recipient, 'install-1', DevicePlatform::Android, 'token-1');
        $this->mock(FcmAccessTokenProvider::class, fn ($mock) => $mock->shouldReceive('isConfigured')->andReturn(true));
        $this->mock(FcmClient::class, fn ($mock) => $mock->shouldReceive('send')->andReturn(FcmSendResult::failure(PushFailureType::Retryable, 'UNAVAILABLE')));

        Queue::fake([SendPushNotificationJob::class]);
        app(NotificationDispatcher::class)->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null);
        $job = Queue::pushed(SendPushNotificationJob::class)->sole();

        try {
            $job->handle(app(PushNotificationService::class));
            $this->fail('Expected a RetryablePushException.');
        } catch (RetryablePushException) {
            // Expected — the push failed, but that must not be treated as a reason to text the user.
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_delivery_logs', 0);
    }

    public function test_a_failed_sms_delivery_never_exposes_the_provider_credential_in_the_notification_log(): void
    {
        Http::fake(['sms.example.com/*' => Http::response('Unauthorized', 500)]);
        $provider = SmsProvider::factory()->create(['is_active' => true, 'config' => [
            'base_url' => 'https://sms.example.com/send',
            'auth_type' => 'BEARER_TOKEN',
            'auth_param_name' => null,
            'username' => null,
            'credential' => 'super-secret-sms-credential-XYZ',
            'sender_id' => 'ONCALL',
            'default_country_code' => '63',
        ]]);
        SmsSetting::current()->update(['enabled' => true, 'active_sms_provider_id' => $provider->id]);
        NotificationSetting::current()->update(['sms_fallback_enabled' => true, 'sms_fallback_events' => ['withdrawal_disbursed']]);
        $recipient = User::factory()->create(['phone' => '+639171234567', 'phone_verified_at' => now()]);

        app(NotificationDispatcher::class)->dispatch($recipient, 'withdrawal_disbursed', ['amount' => '1500.00'], null);

        $log = NotificationDeliveryLog::where('channel', NotificationChannel::Sms)->sole();
        $this->assertSame(NotificationDeliveryStatus::Failed, $log->status);
        $payload = json_encode($log->getAttributes());
        $this->assertStringNotContainsString('super-secret-sms-credential-XYZ', $payload);

        $smsLog = SmsDeliveryLog::sole();
        $this->assertStringNotContainsString('super-secret-sms-credential-XYZ', json_encode($smsLog->getAttributes()));
    }
}
