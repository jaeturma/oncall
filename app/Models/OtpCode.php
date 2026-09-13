<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use App\Services\OtpService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One issued OTP. `otp_hash` is a `Hash::make()` of the code — the
 * plaintext code is never persisted, only returned transiently by
 * {@see OtpService::request()} to build the outgoing SMS.
 */
#[Fillable(['user_id', 'mobile', 'purpose', 'otp_hash', 'expires_at', 'attempts', 'max_attempts', 'sent_at', 'verified_at', 'consumed_at', 'request_ip'])]
#[Hidden(['otp_hash'])]
class OtpCode extends Model
{
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'verified_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasAttemptsRemaining(): bool
    {
        return $this->attempts < $this->max_attempts;
    }

    /** Still usable: not expired, not already consumed, and attempts remain. */
    public function isActive(): bool
    {
        return ! $this->isExpired() && ! $this->isConsumed() && $this->hasAttemptsRemaining();
    }
}
