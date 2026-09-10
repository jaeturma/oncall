<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NoDirectContact implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $containsContact = preg_match('/(?:[\w.+-]+@[\w.-]+\.[a-z]{2,}|https?:\/\/|www\.|facebook\.com|m\.me|telegram|viber|whatsapp)/iu', $value) === 1;
        $digits = preg_replace('/\D+/', '', $value);

        if ($containsContact || (is_string($digits) && strlen($digits) >= 10)) {
            $fail('Remove phone numbers, email addresses, links, and social handles. Stay on Oncall to stay protected.');
        }
    }
}
