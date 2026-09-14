<?php

namespace App\Services\Notifications;

use App\Enums\DevicePlatform;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Device-token lifecycle (Phase M Steps 6-10). A token is looked up two
 * ways on purpose:
 *
 *  - by `fcm_token` first, so a token Firebase has reassigned to a
 *    different installation (shared/reset device, reinstall) is claimed by
 *    whoever is registering it now, instead of erroring on the unique
 *    constraint or silently leaving a stale row pointed at the previous
 *    owner (Step 8 — "prevent push messages from being delivered to the
 *    previous account on a shared device").
 *  - else by `(user_id, installation_id)`, so the same install refreshing
 *    its token updates one row instead of accumulating duplicates.
 */
class DeviceTokenService
{
    public function register(
        User $user,
        string $installationId,
        DevicePlatform $platform,
        string $fcmToken,
        ?string $deviceName = null,
        ?string $appVersion = null,
    ): DeviceToken {
        return DB::transaction(function () use ($user, $installationId, $platform, $fcmToken, $deviceName, $appVersion): DeviceToken {
            $byToken = DeviceToken::query()->where('fcm_token', $fcmToken)->lockForUpdate()->first();

            $attributes = [
                'user_id' => $user->id,
                'installation_id' => $installationId,
                'platform' => $platform,
                'fcm_token' => $fcmToken,
                'device_name' => $deviceName,
                'app_version' => $appVersion,
                'is_active' => true,
                'last_used_at' => now(),
            ];

            if ($byToken !== null) {
                // Reassigning to this user/installation also means any other
                // row this installation previously held (a stale token under
                // the same install id) must not linger active.
                DeviceToken::query()
                    ->where('user_id', $user->id)
                    ->where('installation_id', $installationId)
                    ->whereKeyNot($byToken)
                    ->delete();

                $byToken->update($attributes);

                return $byToken->fresh();
            }

            return DeviceToken::query()->updateOrCreate(
                ['user_id' => $user->id, 'installation_id' => $installationId],
                $attributes,
            );
        });
    }

    /** Per-device logout (Step 9) — only ever deactivates a row the caller owns. */
    public function deactivateForInstallation(User $user, string $installationId): void
    {
        DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('installation_id', $installationId)
            ->update(['is_active' => false]);
    }

    /** Explicit per-record revoke, used by the `DELETE /api/v1/devices/{device}` endpoint. */
    public function deactivate(DeviceToken $deviceToken): void
    {
        $deviceToken->update(['is_active' => false]);
    }

    /** Firebase reported this exact token as permanently invalid/unregistered (Step 10). */
    public function deactivateToken(string $fcmToken): void
    {
        DeviceToken::query()->where('fcm_token', $fcmToken)->update(['is_active' => false]);
    }

    /** @return Collection<int, DeviceToken> */
    public function activeTokensFor(User $user): Collection
    {
        return DeviceToken::query()->where('user_id', $user->id)->where('is_active', true)->get();
    }
}
