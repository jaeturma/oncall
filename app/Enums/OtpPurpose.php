<?php

namespace App\Enums;

/**
 * What an OTP authorizes. An OTP issued for one purpose must never verify
 * another (Phase L) — every `OtpService` call is scoped to exactly one of
 * these, and `OtpCode::verify()` matches on it explicitly.
 */
enum OtpPurpose: string
{
    case MobileVerification = 'MOBILE_VERIFICATION';
}
