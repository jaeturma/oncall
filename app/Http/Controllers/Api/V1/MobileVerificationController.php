<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileNumberNormalizer;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Mobile OTP endpoints (Phase L). Laravel remains the sole authority: this
 * controller never returns the OTP value, never exposes SMS provider
 * configuration, and every rule (normalization, cooldown, rate limits,
 * attempts, purpose binding) is enforced by {@see OtpService} — the
 * Flutter client only ever renders whatever state this returns.
 */
class MobileVerificationController extends Controller
{
    public function request(Request $request, OtpService $otp, MobileNumberNormalizer $normalizer): JsonResponse
    {
        $request->validate(['mobile' => ['required', 'string', 'max:20']]);
        $user = $request->user();

        $normalized = $normalizer->normalize($request->string('mobile')->value());
        if ($normalized === null) {
            return response()->json(['message' => 'Enter a valid Philippine mobile number.'], 422);
        }

        $takenByAnotherAccount = User::query()->where('phone', $normalized)->whereKeyNot($user)->exists();
        if ($takenByAnotherAccount) {
            return response()->json(['message' => 'This mobile number is already registered to another account.'], 422);
        }

        $user->forceFill(['phone_verified_at' => null])->save();

        try {
            $result = $otp->request($user, $normalized, OtpPurpose::MobileVerification, $request->ip());
        } catch (TooManyRequestsHttpException $exception) {
            return $this->tooManyRequests($exception);
        }

        $user->update(['phone' => $result->mobile]);

        if (! $result->sent && $result->plainCode === null) {
            return response()->json(['message' => $result->smsErrorMessage ?? 'We could not send the verification code at this time. Please try again shortly.'], 503);
        }

        return response()->json([
            'message' => 'Verification code sent.',
            'mobile' => $result->mobile,
            'expires_in_seconds' => $result->expiresInSeconds,
            'resend_available_in_seconds' => $result->resendAvailableInSeconds,
            // Only ever populated outside production (see OtpService::request()) — a
            // convenience for QA/demo builds with no SMS provider configured yet.
            'demo_code' => $result->plainCode,
        ]);
    }

    public function resend(Request $request, OtpService $otp): JsonResponse
    {
        $user = $request->user();
        if (blank($user->phone)) {
            return response()->json(['message' => 'Request a verification code before resending one.'], 422);
        }

        try {
            $result = $otp->request($user, $user->phone, OtpPurpose::MobileVerification, $request->ip());
        } catch (TooManyRequestsHttpException $exception) {
            return $this->tooManyRequests($exception);
        }

        if (! $result->sent && $result->plainCode === null) {
            return response()->json(['message' => $result->smsErrorMessage ?? 'We could not send the verification code at this time. Please try again shortly.'], 503);
        }

        return response()->json([
            'message' => 'Verification code resent.',
            'mobile' => $result->mobile,
            'expires_in_seconds' => $result->expiresInSeconds,
            'resend_available_in_seconds' => $result->resendAvailableInSeconds,
            'demo_code' => $result->plainCode,
        ]);
    }

    public function verify(Request $request, OtpService $otp): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits_between:4,8']]);
        $user = $request->user();

        if (blank($user->phone) || ! $otp->verify($user, $user->phone, OtpPurpose::MobileVerification, $request->string('code')->value())) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();

        return response()->json(['message' => 'Mobile number verified.', 'mobile_verified' => true]);
    }

    private function tooManyRequests(TooManyRequestsHttpException $exception): JsonResponse
    {
        $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;

        return response()->json([
            'message' => $exception->getMessage(),
            'retry_after' => $retryAfter !== null ? (int) $retryAfter : null,
        ], 429);
    }
}
