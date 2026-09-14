<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Generalized sibling of {@see SafeSmsTemplate} for admin-managed
 * notification templates (Phase M Step 24) — plain text with controlled
 * token replacement, never Blade/PHP. Unlike the SMS template, the allowed
 * placeholder set is per-event (passed in), not a fixed three, and no
 * placeholder is required.
 */
class SafeNotificationTemplate implements ValidationRule
{
    /** @param  list<string>  $allowedPlaceholders  Bare names, e.g. ['amount', 'provider_name']. */
    public function __construct(private readonly array $allowedPlaceholders) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $allowedPattern = implode('|', array_map(fn (string $name): string => preg_quote($name, '/'), $this->allowedPlaceholders));
        $disallowedPlaceholder = $allowedPattern === ''
            ? '/\{\{/'
            : '/\{\{\s*(?!('.$allowedPattern.')\s*\}\})/i';

        if (preg_match('/<\?php|\{!!|@\w+\(/i', $value) === 1 || preg_match($disallowedPlaceholder, $value) === 1) {
            $fail('The template may only use the following placeholders: '.($this->allowedPlaceholders === [] ? '(none for this event)' : implode(', ', array_map(fn (string $name): string => '{{'.$name.'}}', $this->allowedPlaceholders))).' — no other code or tags.');

            return;
        }

        $stripped = $value;
        foreach ($this->allowedPlaceholders as $name) {
            $stripped = str_replace('{{'.$name.'}}', '', $stripped);
        }

        if (str_contains($stripped, '{{') || str_contains($stripped, '}}')) {
            $fail('The template contains a malformed placeholder.');
        }
    }
}
