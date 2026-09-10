<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A single parametric in-app notification. Every Oncall event uses this shape so
 * the inbox can render any of them uniformly; `key` identifies the event type.
 */
class OncallEvent extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $url = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
