<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * A minimal OTP-style mobile verification flow. There is no SMS gateway
 * integration (per the "no paid APIs" MVP constraint), so the code is
 * written to the application log the same way MAIL_MAILER=log stands in for
 * a real mail transport — good enough for a demo/manual-QA environment,
 * not for production SMS delivery.
 */
class MobileVerificationService
{
    private const TTL_MINUTES = 10;

    public function issueCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->cacheKey($user), Hash::make($code), now()->addMinutes(self::TTL_MINUTES));
        Log::info('Mobile verification code issued', ['user_id' => $user->id, 'phone' => $user->phone, 'code' => $code]);

        return $code;
    }

    public function verify(User $user, string $code): bool
    {
        $hashed = Cache::get($this->cacheKey($user));

        if ($hashed === null || ! Hash::check($code, $hashed)) {
            return false;
        }

        Cache::forget($this->cacheKey($user));
        $user->forceFill(['phone_verified_at' => now()])->save();

        return true;
    }

    public function hasPendingCode(User $user): bool
    {
        return Cache::has($this->cacheKey($user));
    }

    private function cacheKey(User $user): string
    {
        return "mobile-verification-otp:{$user->id}";
    }
}
