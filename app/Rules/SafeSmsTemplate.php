<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * The OTP message template is plain text with controlled token
 * replacement (Phase L) — never Blade/PHP. Only `{{otp}}`, `{{minutes}}`,
 * and `{{app_name}}` are recognized placeholders; anything else shaped
 * like a placeholder, or any PHP/Blade-looking construct, is rejected.
 */
class SafeSmsTemplate implements ValidationRule
{
    private const ALLOWED_PLACEHOLDERS = ['{{otp}}', '{{minutes}}', '{{app_name}}'];

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (! str_contains($value, '{{otp}}')) {
            $fail('The message template must include the {{otp}} placeholder.');

            return;
        }

        if (preg_match('/<\?php|\{\{\s*(?!otp|minutes|app_name)|\{!!|@\w+\(/i', $value) === 1) {
            $fail('The message template may only use the {{otp}}, {{minutes}}, and {{app_name}} placeholders — no other code or tags.');

            return;
        }

        $stripped = str_replace(self::ALLOWED_PLACEHOLDERS, '', $value);
        if (str_contains($stripped, '{{') || str_contains($stripped, '}}')) {
            $fail('The message template may only use the {{otp}}, {{minutes}}, and {{app_name}} placeholders.');
        }
    }
}
