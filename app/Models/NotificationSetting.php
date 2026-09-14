<?php

namespace App\Models;

use App\Services\Notifications\NotificationCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id 1) holding the global push/SMS-fallback switches
 * — mirrors {@see SmsSetting} from Phase L. Always go through
 * {@see current()} rather than querying the table directly.
 */
#[Fillable(['push_enabled', 'sms_fallback_enabled', 'sms_fallback_events', 'retry_max_attempts'])]
class NotificationSetting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'push_enabled' => false,
        'sms_fallback_enabled' => false,
        'retry_max_attempts' => 3,
    ];

    protected function casts(): array
    {
        return [
            'push_enabled' => 'boolean',
            'sms_fallback_enabled' => 'boolean',
            'sms_fallback_events' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    /** @return list<string> */
    public function enabledSmsFallbackEvents(): array
    {
        if (! $this->sms_fallback_enabled) {
            return [];
        }

        return array_values(array_intersect(
            $this->sms_fallback_events ?? [],
            NotificationCatalog::smsFallbackEligibleKeys(),
        ));
    }
}
