<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use Database\Factories\NotificationDeliveryLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One delivery attempt on one channel (database/push/sms) for one event —
 * mirrors {@see SmsDeliveryLog} from Phase L. Deliberately has no payload
 * body column: the rendered title/body is never persisted here, only enough
 * metadata for the admin log viewer (Phase M Step 38).
 */
#[Fillable(['notification_id', 'user_id', 'event_key', 'channel', 'provider', 'device_id', 'status', 'provider_message_id', 'attempt_count', 'queued_at', 'sent_at', 'failed_at', 'failure_code'])]
class NotificationDeliveryLog extends Model
{
    /** @use HasFactory<NotificationDeliveryLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationDeliveryStatus::class,
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceToken::class, 'device_id');
    }
}
