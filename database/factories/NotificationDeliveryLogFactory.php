<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDeliveryLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationDeliveryLog>
 */
class NotificationDeliveryLogFactory extends Factory
{
    protected $model = NotificationDeliveryLog::class;

    public function definition(): array
    {
        return [
            'notification_id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'event_key' => 'new_message',
            'channel' => NotificationChannel::Database,
            'provider' => null,
            'device_id' => null,
            'status' => NotificationDeliveryStatus::Sent,
            'provider_message_id' => null,
            'attempt_count' => 1,
            'queued_at' => now(),
            'sent_at' => now(),
            'failed_at' => null,
            'failure_code' => null,
        ];
    }
}
