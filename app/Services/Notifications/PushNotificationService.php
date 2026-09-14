<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\PushFailureType;
use App\Models\DeviceToken;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\Notifications\Fcm\FcmAccessTokenProvider;
use App\Services\Notifications\Fcm\FcmClient;

/**
 * Server-side push sender (Phase M Step 11). Resolves a user's active
 * devices, sends through FCM, and records one {@see NotificationDeliveryLog}
 * row per device regardless of outcome — never claims `delivered` (FCM's
 * HTTP v1 API only confirms acceptance, not device delivery; see Step 13).
 */
class PushNotificationService
{
    public function __construct(
        private readonly FcmClient $fcm,
        private readonly FcmAccessTokenProvider $tokenProvider,
        private readonly DeviceTokenService $deviceTokens,
    ) {}

    /**
     * @param  array{screen: string, id?: int|string}|null  $target
     *
     * @throws RetryablePushException
     */
    public function sendToUser(User $user, string $eventKey, ?string $notificationId, string $title, string $body, ?array $target): void
    {
        $settings = NotificationSetting::current();

        if (! $settings->push_enabled) {
            $this->logSkipped($user, $eventKey, $notificationId, 'push_disabled');

            return;
        }

        if (! $this->tokenProvider->isConfigured()) {
            $this->logSkipped($user, $eventKey, $notificationId, 'push_not_configured');

            return;
        }

        $tokens = $this->deviceTokens->activeTokensFor($user);

        if ($tokens->isEmpty()) {
            $this->logSkipped($user, $eventKey, $notificationId, 'no_active_devices');

            return;
        }

        $data = array_map('strval', array_filter([
            'event' => $eventKey,
            'notification_id' => $notificationId,
            'screen' => $target['screen'] ?? null,
            'target_id' => isset($target['id']) ? (string) $target['id'] : null,
        ], fn (mixed $value): bool => $value !== null));

        $hadRetryableFailure = false;

        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $eventKey, $notificationId, $title, $body, $data) === PushFailureType::Retryable) {
                $hadRetryableFailure = true;
            }
        }

        if ($hadRetryableFailure) {
            throw new RetryablePushException("Retryable FCM failure sending event [{$eventKey}] to user [{$user->id}].");
        }
    }

    /** @param  array<string, string>  $data */
    private function sendToToken(DeviceToken $token, string $eventKey, ?string $notificationId, string $title, string $body, array $data): ?PushFailureType
    {
        $log = NotificationDeliveryLog::create([
            'notification_id' => $notificationId,
            'user_id' => $token->user_id,
            'event_key' => $eventKey,
            'channel' => NotificationChannel::Push,
            'provider' => 'fcm',
            'device_id' => $token->id,
            'status' => NotificationDeliveryStatus::Queued,
            'attempt_count' => 1,
            'queued_at' => now(),
        ]);

        $result = $this->fcm->send($token->fcm_token, $title, $body, $data);

        if ($result->successful) {
            $log->update([
                'status' => NotificationDeliveryStatus::Sent,
                'provider_message_id' => $result->providerMessageId,
                'sent_at' => now(),
            ]);

            return null;
        }

        $log->update([
            'status' => $result->failureType === PushFailureType::InvalidToken
                ? NotificationDeliveryStatus::InvalidToken
                : NotificationDeliveryStatus::Failed,
            'failure_code' => $result->errorCode,
            'failed_at' => now(),
        ]);

        if ($result->failureType === PushFailureType::InvalidToken) {
            $this->deviceTokens->deactivateToken($token->fcm_token);
        }

        return $result->failureType;
    }

    private function logSkipped(User $user, string $eventKey, ?string $notificationId, string $reason): void
    {
        NotificationDeliveryLog::create([
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'event_key' => $eventKey,
            'channel' => NotificationChannel::Push,
            'status' => NotificationDeliveryStatus::Skipped,
            'failure_code' => $reason,
            'queued_at' => now(),
        ]);
    }
}
