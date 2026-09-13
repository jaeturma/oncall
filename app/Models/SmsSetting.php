<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Singleton row (always id 1) holding the global SMS on/off switch, the
 * active provider, and every admin-configurable OTP policy bound. Always
 * go through {@see current()} rather than querying the table directly.
 */
#[Fillable([
    'enabled', 'active_sms_provider_id', 'otp_length', 'otp_expiry_minutes',
    'otp_resend_cooldown_seconds', 'otp_max_attempts', 'otp_max_sends_per_mobile_per_hour',
    'otp_max_sends_per_ip_per_hour', 'test_mobile_number', 'otp_message_template',
])]
class SmsSetting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'enabled' => false,
        'otp_length' => 6,
        'otp_expiry_minutes' => 5,
        'otp_resend_cooldown_seconds' => 60,
        'otp_max_attempts' => 5,
        'otp_max_sends_per_mobile_per_hour' => 5,
        'otp_max_sends_per_ip_per_hour' => 20,
        'otp_message_template' => 'Your {{app_name}} verification code is {{otp}}. It expires in {{minutes}} minutes. Do not share this code with anyone.',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function activeProvider(): BelongsTo
    {
        return $this->belongsTo(SmsProvider::class, 'active_sms_provider_id');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
