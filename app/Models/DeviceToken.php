<?php

namespace App\Models;

use App\Enums\DevicePlatform;
use Database\Factories\DeviceTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Flutter installation's push registration. Keyed for lookups two ways
 * (see the migration): by `fcm_token` (so a token that Firebase reassigns to
 * a different installation is found and reassigned safely — Phase M Step 8)
 * and by `(user_id, installation_id)` (so the same install refreshing its
 * token updates one row instead of accumulating duplicates).
 *
 * Deliberately has no IMEI/hardware-serial column — `installation_id` is an
 * app-generated UUID, never a device-identifying value (Phase M Step 6).
 */
#[Fillable(['user_id', 'installation_id', 'platform', 'fcm_token', 'device_name', 'app_version', 'is_active', 'last_used_at'])]
class DeviceToken extends Model
{
    /** @use HasFactory<DeviceTokenFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'platform' => DevicePlatform::class,
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** `eb3f****a91c` — never render a full token to an admin view. */
    public function maskedToken(): string
    {
        $token = $this->fcm_token;
        if (mb_strlen($token) <= 8) {
            return str_repeat('*', mb_strlen($token));
        }

        return mb_substr($token, 0, 4).str_repeat('*', 4).mb_substr($token, -4);
    }
}
