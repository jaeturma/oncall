<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMobileVerificationCodeRequest;
use App\Http\Requests\VerifyMobileCodeRequest;
use App\Services\MobileVerificationService;
use Illuminate\Http\RedirectResponse;

class MobileVerificationController extends Controller
{
    public function send(SendMobileVerificationCodeRequest $request, MobileVerificationService $mobileVerification): RedirectResponse
    {
        $user = $request->user();
        $user->update(['phone' => $request->string('phone')->value()]);
        // Not mass-assignable on purpose (see the User model's #[Fillable] list) —
        // clearing it here is deliberate, not a form re-submitting stale data.
        $user->forceFill(['phone_verified_at' => null])->save();
        $code = $mobileVerification->issueCode($user);

        $status = 'We sent a 6-digit code to '.$user->phone.'.';

        if (! app()->isProduction()) {
            $status .= " Demo code (no SMS gateway configured): {$code}.";
        }

        return redirect()->route('verification.index')->with('status', $status);
    }

    public function verify(VerifyMobileCodeRequest $request, MobileVerificationService $mobileVerification): RedirectResponse
    {
        if (! $mobileVerification->verify($request->user(), $request->string('code')->value())) {
            return back()->withErrors(['code' => 'That code is incorrect or has expired. Request a new one.']);
        }

        return redirect()->route('verification.index')->with('status', 'Your mobile number is verified.');
    }
}
