<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\SmsSetting;
use App\Models\User;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Owns the full OTP lifecycle (Phase L): generate, persist as a hash, send
 * via {@see SmsManager}, verify, expire, and enforce cooldown/rate-limit/
 * attempt policy — all read from the live {@see SmsSetting} row so an
 * admin's policy changes take effect immediately, with no restart needed.
 *
 * The plaintext code exists only inside {@see request()}'s stack frame,
 * long enough to hash it and render the outgoing message; it is never
 * returned to callers except via the `plainCode` on the result, which is
 * only populated outside production (see `MobileVerificationController`'s
 * demo-code convenience) and is never logged.
 */
class OtpService
{
    public function __construct(
        private readonly SmsManager $sms,
        private readonly MobileNumberNormalizer $normalizer,
    ) {}

    /**
     * @throws TooManyRequestsHttpException if the resend cooldown or an hourly cap is still in effect.
     */
    public function request(?User $user, string $mobile, OtpPurpose $purpose, ?string $requestIp): OtpRequestResult
    {
        $normalized = $this->normalizer->normalize($mobile);
        if ($normalized === null) {
            throw new ConflictHttpException('Enter a valid Philippine mobile number.');
        }

        $settings = SmsSetting::current();
        $this->assertNotInCooldown($normalized, $purpose, $settings->otp_resend_cooldown_seconds);
        $this->assertUnderHourlyCaps($normalized, $requestIp, $purpose, $settings);

        $code = $this->generateCode($settings->otp_length);

        $otp = DB::transaction(function () use ($user, $normalized, $purpose, $code, $settings, $requestIp): OtpCode {
            // Superseding any still-open code for the same user+mobile+purpose
            // means only the most recently sent code can ever verify.
            OtpCode::query()
                ->where('user_id', $user?->id)
                ->where('mobile', $normalized)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return OtpCode::create([
                'user_id' => $user?->id,
                'mobile' => $normalized,
                'purpose' => $purpose,
                'otp_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes($settings->otp_expiry_minutes),
                'max_attempts' => $settings->otp_max_attempts,
                'sent_at' => now(),
                'request_ip' => $requestIp,
            ]);
        });

        $message = $this->renderTemplate($settings->otp_message_template, $code, $settings->otp_expiry_minutes);
        $result = $this->sms->send(new SmsMessage($normalized, $message, 'OTP_VERIFICATION', $purpose), $user);

        return new OtpRequestResult(
            sent: $result->successful,
            mobile: $normalized,
            expiresInSeconds: $settings->otp_expiry_minutes * 60,
            resendAvailableInSeconds: $settings->otp_resend_cooldown_seconds,
            plainCode: app()->isProduction() ? null : $code,
            smsErrorMessage: $result->successful ? null : $result->errorMessage,
        );
    }

    /**
     * Generic failure message regardless of *why* the code didn't verify
     * (expired, wrong, already used, wrong purpose) — never reveal which,
     * so an attacker can't distinguish a live guess from a dead one.
     */
    public function verify(?User $user, string $mobile, OtpPurpose $purpose, string $code): bool
    {
        $normalized = $this->normalizer->normalize($mobile) ?? $mobile;

        return DB::transaction(function () use ($user, $normalized, $purpose, $code): bool {
            $otp = OtpCode::query()
                ->where('user_id', $user?->id)
                ->where('mobile', $normalized)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null || ! $otp->isActive()) {
                return false;
            }

            if (! Hash::check($code, $otp->otp_hash)) {
                $otp->increment('attempts');
                if (! $otp->hasAttemptsRemaining()) {
                    $otp->update(['consumed_at' => now()]);
                }

                return false;
            }

            $otp->update(['verified_at' => now(), 'consumed_at' => now()]);

            return true;
        });
    }

    /** Whether the user has an unconsumed, unexpired code outstanding for this purpose. */
    public function hasActiveCode(User $user, OtpPurpose $purpose): bool
    {
        return OtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    private function assertNotInCooldown(string $mobile, OtpPurpose $purpose, int $cooldownSeconds): void
    {
        $lastSentAt = OtpCode::query()->where('mobile', $mobile)->where('purpose', $purpose)->latest('sent_at')->value('sent_at');

        if ($lastSentAt === null) {
            return;
        }

        $availableAt = $lastSentAt->clone()->addSeconds($cooldownSeconds);
        if ($availableAt->isFuture()) {
            throw new TooManyRequestsHttpException(
                (int) now()->diffInSeconds($availableAt, true),
                'Please wait before requesting another verification code.',
            );
        }
    }

    private function assertUnderHourlyCaps(string $mobile, ?string $requestIp, OtpPurpose $purpose, SmsSetting $settings): void
    {
        $since = now()->subHour();

        $perMobile = OtpCode::query()->where('mobile', $mobile)->where('purpose', $purpose)->where('sent_at', '>=', $since)->count();
        if ($perMobile >= $settings->otp_max_sends_per_mobile_per_hour) {
            throw new TooManyRequestsHttpException(3600, 'Too many verification codes requested for this number. Please try again later.');
        }

        if ($requestIp !== null) {
            $perIp = OtpCode::query()->where('request_ip', $requestIp)->where('purpose', $purpose)->where('sent_at', '>=', $since)->count();
            if ($perIp >= $settings->otp_max_sends_per_ip_per_hour) {
                throw new TooManyRequestsHttpException(3600, 'Too many verification codes requested from this connection. Please try again later.');
            }
        }
    }

    private function generateCode(int $length): string
    {
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function renderTemplate(string $template, string $code, int $expiryMinutes): string
    {
        return Str::of($template)
            ->replace('{{otp}}', $code)
            ->replace('{{minutes}}', (string) $expiryMinutes)
            ->replace('{{app_name}}', config('app.name'))
            ->value();
    }
}
