<?php

namespace App\Http\Controllers;

use App\Enums\OtpPurpose;
use App\Http\Requests\SendMobileVerificationCodeRequest;
use App\Http\Requests\VerifyMobileCodeRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MobileVerificationController extends Controller
{
    public function send(SendMobileVerificationCodeRequest $request, OtpService $otp): RedirectResponse
    {
        $user = $request->user();
        // Not mass-assignable on purpose (see the User model's #[Fillable] list) —
        // clearing it here is deliberate, not a form re-submitting stale data.
        $user->forceFill(['phone_verified_at' => null])->save();

        try {
            $result = $otp->request($user, $request->string('phone')->value(), OtpPurpose::MobileVerification, $request->ip());
        } catch (HttpException $exception) {
            return back()->withErrors(['phone' => $exception->getMessage()]);
        }

        $user->update(['phone' => $result->mobile]);

        // A demo code (non-production only — see OtpService::request()) means
        // the code was still generated and is usable even though no real SMS
        // provider is configured yet; only a genuine send failure blocks the
        // user here.
        if (! $result->sent && $result->plainCode === null) {
            return back()->withErrors(['phone' => 'We could not send the verification code at this time. Please try again shortly.']);
        }

        $status = 'We sent a code to '.$result->mobile.'.';
        if ($result->plainCode !== null) {
            $status .= " Demo code (no SMS provider configured): {$result->plainCode}.";
        }

        return redirect()->route('verification.index')->with('status', $status);
    }

    public function verify(VerifyMobileCodeRequest $request, OtpService $otp): RedirectResponse
    {
        $user = $request->user();

        if (! $otp->verify($user, (string) $user->phone, OtpPurpose::MobileVerification, $request->string('code')->value())) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();

        return redirect()->route('verification.index')->with('status', 'Your mobile number is verified.');
    }
}
