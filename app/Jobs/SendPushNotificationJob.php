<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued so a Firebase outage never blocks the business transaction that
 * triggered it (Phase M Step 12/41) — dispatched only via
 * `NotificationDispatcher`, which sets `afterCommit` semantics through this
 * job's `$afterCommit` property so it never fires before the underlying
 * database transaction actually commits.
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param  array{screen: string, id?: int|string}|null  $target */
    public function __construct(
        public readonly int $userId,
        public readonly string $eventKey,
        public readonly ?string $notificationId,
        public readonly string $title,
        public readonly string $body,
        public readonly ?array $target,
    ) {
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(PushNotificationService $push): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        $push->sendToUser($user, $this->eventKey, $this->notificationId, $this->title, $this->body, $this->target);
    }
}
