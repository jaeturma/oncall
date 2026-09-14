<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Jobs\SendPushNotificationJob;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Notifications\OncallEvent;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsMessage;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The single entry point business workflows call to raise a notification
 * event (Phase M Step 2) — the semantic equivalent of Phase L's `SmsManager`
 * for notifications. Business code never touches Firebase, the `notifications`
 * table, or `SmsManager` directly.
 *
 * `dedupKey`, when given, is claimed only once the underlying transaction has
 * actually committed (see the `DB::afterCommit` closure below) — claiming it
 * eagerly would mean a transaction that gets rolled back and retried (Step
 * 32's "database transaction retries") permanently swallows the notification
 * for the eventually-successful attempt.
 */
class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationTemplateService $templates,
        private readonly NotificationPreferenceService $preferences,
        private readonly SmsManager $sms,
    ) {}

    /**
     * @param  array<string, string|int>  $placeholders  Bare names (no braces) => value, matching the event's catalog `placeholders` list.
     * @param  array{screen?: string, id?: int|string}|null  $target
     */
    public function dispatch(?User $recipient, string $eventKey, array $placeholders = [], ?array $target = null, ?string $dedupKey = null): void
    {
        if ($recipient === null) {
            return;
        }

        $resolvedTarget = NotificationCatalog::resolveTarget($target);
        $databaseText = $this->templates->render($eventKey, 'database', $placeholders);

        DB::afterCommit(function () use ($recipient, $eventKey, $databaseText, $resolvedTarget, $placeholders, $dedupKey): void {
            if ($dedupKey !== null && ! Cache::add('notification-dedup:'.$dedupKey, true, now()->addMinutes(5))) {
                return;
            }

            $notification = $this->createDatabaseNotification($recipient, $eventKey, $databaseText['title'], $databaseText['body'], $resolvedTarget);

            NotificationDeliveryLog::create([
                'notification_id' => $notification->id,
                'user_id' => $recipient->id,
                'event_key' => $eventKey,
                'channel' => NotificationChannel::Database,
                'status' => NotificationDeliveryStatus::Sent,
                'attempt_count' => 1,
                'queued_at' => now(),
                'sent_at' => now(),
            ]);

            if ($this->preferences->pushAllowed($recipient, $eventKey)) {
                $pushText = $this->templates->render($eventKey, 'push', $placeholders);
                SendPushNotificationJob::dispatch($recipient->id, $eventKey, $notification->id, $pushText['title'], $pushText['body'], $resolvedTarget);
            }

            $this->maybeSendSmsFallback($recipient, $eventKey, $databaseText['body']);
        });
    }

    private function createDatabaseNotification(User $recipient, string $eventKey, string $title, string $body, ?array $target): DatabaseNotification
    {
        return $recipient->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => OncallEvent::class,
            'data' => ['key' => $eventKey, 'title' => $title, 'body' => $body, 'target' => $target],
            'read_at' => null,
        ]);
    }

    /**
     * Step 33: only ever fires for an event the catalog marks eligible AND
     * the admin has explicitly enabled for fallback AND the user hasn't
     * opted out AND the number is a verified one — never a speculative
     * "push might have failed" trigger.
     */
    private function maybeSendSmsFallback(User $recipient, string $eventKey, string $body): void
    {
        if (! NotificationCatalog::isSmsFallbackEligible($eventKey)) {
            return;
        }

        if (! in_array($eventKey, NotificationSetting::current()->enabledSmsFallbackEvents(), true)) {
            return;
        }

        if (! $this->preferences->smsAllowed($recipient, $eventKey)) {
            return;
        }

        if (blank($recipient->phone) || $recipient->phone_verified_at === null) {
            return;
        }

        $result = $this->sms->send(new SmsMessage($recipient->phone, $body, 'NOTIFICATION_FALLBACK'), $recipient);

        NotificationDeliveryLog::create([
            'user_id' => $recipient->id,
            'event_key' => $eventKey,
            'channel' => NotificationChannel::Sms,
            'status' => $result->successful ? NotificationDeliveryStatus::Sent : NotificationDeliveryStatus::Failed,
            'failure_code' => $result->errorCode,
            'attempt_count' => 1,
            'queued_at' => now(),
            'sent_at' => $result->successful ? now() : null,
            'failed_at' => $result->successful ? null : now(),
        ]);
    }
}
