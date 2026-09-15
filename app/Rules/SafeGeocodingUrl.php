<?php

namespace App\Rules;

use App\Services\Sms\SmsUrlValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

/**
 * Rejects a geocoding provider base URL that would let the server be used
 * for SSRF. Delegates to {@see SmsUrlValidator}, which despite its name is a
 * generic "safe to let the server fetch this admin-configured URL" check —
 * reused here rather than duplicated, exactly like {@see SafeSmsUrl}.
 */
class SafeGeocodingUrl implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        try {
            SmsUrlValidator::assertSafe($value);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
