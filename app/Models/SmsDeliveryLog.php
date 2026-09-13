<?php

namespace App\Models;

use App\Enums\SmsDeliveryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One send attempt. Deliberately has no `body`/`message` column — the
 * rendered SMS text (which, for OTP sends, contains the plaintext code)
 * is never persisted here. `message_type` alone (e.g. `OTP_VERIFICATION`)
 * is enough context for the admin log viewer.
 */
#[Fillable(['user_id', 'mobile', 'provider', 'purpose', 'message_type', 'provider_message_id', 'status', 'queued_at', 'sent_at', 'failed_at', 'error_code'])]
class SmsDeliveryLog extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SmsDeliveryStatus::class,
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** `+639171****67` — never render the raw `mobile` column to an admin view. */
    public function maskedMobile(): string
    {
        $mobile = $this->mobile;
        if (mb_strlen($mobile) <= 6) {
            return str_repeat('*', mb_strlen($mobile));
        }

        return mb_substr($mobile, 0, 7).str_repeat('*', mb_strlen($mobile) - 9).mb_substr($mobile, -2);
    }
}
