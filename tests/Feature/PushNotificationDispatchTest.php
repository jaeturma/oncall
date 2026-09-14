<?php

namespace Tests\Feature;

use App\Enums\DevicePlatform;
use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\PushFailureType;
use App\Jobs\SendPushNotificationJob;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\Notifications\DeviceTokenService;
use App\Services\Notifications\Fcm\FcmAccessTokenProvider;
use App\Services\Notifications\Fcm\FcmClient;
use App\Services\Notifications\Fcm\FcmSendResult;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\PushNotificationService;
use App\Services\Notifications\RetryablePushException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Phase M Steps 11-13, 32, 41-42 — the push pipeline
 * (NotificationDispatcher -> SendPushNotificationJob -> PushNotificationService
 * -> FcmClient). FcmClient and FcmAccessTokenProvider are mocked rather than
 * faked over HTTP: both are the one place in the app that actually leaves the
 * process to talk to Firebase, so real network/credential material is not
 * needed to exercise the retry/deactivation/dedup decisions this pipeline
 * makes around them.
 */
class PushNotificationDispatchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_business_event_creates_a_database_notification_and_queues_a_push_job(): void
    {
        Queue::fake([SendPushNotificationJob::class]);
        $recipient = User::factory()->create();

        app(NotificationDispatcher::class)->dispatch(
            $recipient,
            'new_message',
            ['sender_name' => 'Pedro'],
            ['screen' => 'conversation', 'id' => 42],
        );

        $notification = $recipient->notifications()->sole();
        $this->assertSame('new_message', $notification->data['key']);
        $this->assertStringContainsString('Pedro', $notification->data['body']);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'notification_id' => $notification->id,
            'channel' => NotificationChannel::Database->value,
            'status' => NotificationDeliveryStatus::Sent->value,
        ]);

        Queue::assertPushed(SendPushNotificationJob::class, fn (SendPushNotificationJob $job): bool => $job->userId === $recipient->id
            && $job->eventKey === 'new_message'
            && $job->notificationId === $notification->id
            && $job->target === ['screen' => 'conversation', 'id' => 42]);
    }

    public function test_a_push_job_failure_does_not_roll_back_already_persisted_notification_data(): void
    {
        $this->enablePush();
        $this->mockFcmResult(FcmSendResult::failure(PushFailureType::Retryable, 'UNAVAILABLE'));
        $recipient = User::factory()->create();
        app(DeviceTokenService::class)->register($recipient, 'install-1', DevicePlatform::Android, 'token-1');

        Queue::fake([SendPushNotificationJob::class]);
        app(NotificationDispatcher::class)->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null);
        $notification = $recipient->notifications()->sole();
        $job = Queue::pushed(SendPushNotificationJob::class)->sole();

        try {
            $job->handle(app(PushNotificationService::class));
            $this->fail('Expected a RetryablePushException.');
        } catch (RetryablePushException) {
            // Expected — Laravel's queue worker would retry this with backoff.
        }

        // The already-committed database notification is untouched by the later push failure.
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertDatabaseHas('notification_delivery_logs', [
            'notification_id' => $notification->id,
            'channel' => NotificationChannel::Push->value,
            'status' => NotificationDeliveryStatus::Failed->value,
            'failure_code' => 'UNAVAILABLE',
        ]);

        // A transient failure must not deactivate the device — it will be retried.
        $this->assertTrue(app(DeviceTokenService::class)->activeTokensFor($recipient)->isNotEmpty());
    }

    public function test_a_permanently_invalid_token_is_deactivated_without_retrying(): void
    {
        $this->enablePush();
        $this->mockFcmResult(FcmSendResult::failure(PushFailureType::InvalidToken, 'UNREGISTERED'));
        $recipient = User::factory()->create();
        $device = app(DeviceTokenService::class)->register($recipient, 'install-1', DevicePlatform::Android, 'token-1');

        Queue::fake([SendPushNotificationJob::class]);
        app(NotificationDispatcher::class)->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null);
        $job = Queue::pushed(SendPushNotificationJob::class)->sole();

        // Does not throw — an invalid token is not a retryable condition.
        $job->handle(app(PushNotificationService::class));

        $this->assertFalse($device->fresh()->is_active);
        $this->assertDatabaseHas('notification_delivery_logs', [
            'device_id' => $device->id,
            'status' => NotificationDeliveryStatus::InvalidToken->value,
            'failure_code' => 'UNREGISTERED',
        ]);
    }

    #[TestWith([PushFailureType::Permanent, 'INTERNAL_ERROR'])]
    #[TestWith([PushFailureType::ConfigurationError, 'INVALID_ARGUMENT'])]
    public function test_non_retryable_failures_do_not_throw_and_do_not_deactivate_the_token(PushFailureType $failureType, string $errorCode): void
    {
        $this->enablePush();
        $this->mockFcmResult(FcmSendResult::failure($failureType, $errorCode));
        $recipient = User::factory()->create();
        $device = app(DeviceTokenService::class)->register($recipient, 'install-1', DevicePlatform::Android, 'token-1');

        Queue::fake([SendPushNotificationJob::class]);
        app(NotificationDispatcher::class)->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null);
        $job = Queue::pushed(SendPushNotificationJob::class)->sole();

        $job->handle(app(PushNotificationService::class));

        $this->assertTrue($device->fresh()->is_active);
        $this->assertDatabaseHas('notification_delivery_logs', [
            'device_id' => $device->id,
            'status' => NotificationDeliveryStatus::Failed->value,
            'failure_code' => $errorCode,
        ]);
    }

    public function test_a_retryable_failure_on_one_device_does_not_block_delivery_to_other_active_devices(): void
    {
        $this->enablePush();
        $recipient = User::factory()->create();
        $devices = app(DeviceTokenService::class);
        $devices->register($recipient, 'install-1', DevicePlatform::Android, 'good-token');
        $devices->register($recipient, 'install-2', DevicePlatform::Ios, 'bad-token');

        $this->mock(FcmAccessTokenProvider::class, fn ($mock) => $mock->shouldReceive('isConfigured')->andReturn(true));
        $this->mock(FcmClient::class, function ($mock): void {
            $mock->shouldReceive('send')->with('good-token', \Mockery::any(), \Mockery::any(), \Mockery::any())->andReturn(FcmSendResult::success('msg-1'));
            $mock->shouldReceive('send')->with('bad-token', \Mockery::any(), \Mockery::any(), \Mockery::any())->andReturn(FcmSendResult::failure(PushFailureType::Retryable, 'UNAVAILABLE'));
        });

        Queue::fake([SendPushNotificationJob::class]);
        app(NotificationDispatcher::class)->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null);
        $job = Queue::pushed(SendPushNotificationJob::class)->sole();

        try {
            $job->handle(app(PushNotificationService::class));
            $this->fail('Expected a RetryablePushException.');
        } catch (RetryablePushException) {
        }

        $this->assertDatabaseHas('notification_delivery_logs', ['provider_message_id' => 'msg-1', 'status' => NotificationDeliveryStatus::Sent->value]);
        $this->assertDatabaseHas('notification_delivery_logs', ['failure_code' => 'UNAVAILABLE', 'status' => NotificationDeliveryStatus::Failed->value]);
    }

    public function test_duplicate_dispatch_with_the_same_dedup_key_only_creates_one_notification(): void
    {
        Queue::fake([SendPushNotificationJob::class]);
        $recipient = User::factory()->create();
        $dispatcher = app(NotificationDispatcher::class);

        $dispatcher->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null, dedupKey: 'msg:conversation:1');
        $dispatcher->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null, dedupKey: 'msg:conversation:1');

        $this->assertSame(1, $recipient->notifications()->count());
        Queue::assertPushedTimes(SendPushNotificationJob::class, 1);
    }

    public function test_a_different_dedup_key_is_not_suppressed(): void
    {
        Queue::fake([SendPushNotificationJob::class]);
        $recipient = User::factory()->create();
        $dispatcher = app(NotificationDispatcher::class);

        $dispatcher->dispatch($recipient, 'new_message', ['sender_name' => 'Ana'], null, dedupKey: 'msg:conversation:1');
        $dispatcher->dispatch($recipient, 'new_message', ['sender_name' => 'Ben'], null, dedupKey: 'msg:conversation:2');

        $this->assertSame(2, $recipient->notifications()->count());
        Queue::assertPushedTimes(SendPushNotificationJob::class, 2);
    }

    public function test_push_is_skipped_when_the_admin_has_disabled_it(): void
    {
        NotificationSetting::current()->update(['push_enabled' => false]);
        $recipient = User::factory()->create();
        app(DeviceTokenService::class)->register($recipient, 'install-1', DevicePlatform::Android, 'token-1');

        app(PushNotificationService::class)->sendToUser($recipient, 'new_message', null, 'Title', 'Body', null);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'user_id' => $recipient->id,
            'status' => NotificationDeliveryStatus::Skipped->value,
            'failure_code' => 'push_disabled',
        ]);
    }

    private function enablePush(): void
    {
        NotificationSetting::current()->update(['push_enabled' => true]);
    }

    private function mockFcmResult(FcmSendResult $result): void
    {
        $this->mock(FcmAccessTokenProvider::class, fn ($mock) => $mock->shouldReceive('isConfigured')->andReturn(true));
        $this->mock(FcmClient::class, fn ($mock) => $mock->shouldReceive('send')->andReturn($result));
    }
}
