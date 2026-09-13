<?php

namespace App\Services;

/**
 * Canonicalizes a Philippine mobile number to `+639XXXXXXXXX` so every
 * consumer (registration, OTP requests, provider search, ...) compares and
 * stores the same format regardless of how the user typed it in. Kept as
 * one reusable service rather than scattering `preg_replace` calls through
 * controllers/requests (Phase L). International numbers are out of scope
 * for now but deliberately not precluded by this class's shape.
 */
class MobileNumberNormalizer
{
    private const PATTERN = '/^(?:\+?63|0)?9(\d{9})$/';

    /**
     * @return string|null The canonical `+639XXXXXXXXX` form, or null if the input isn't a recognizable PH mobile number.
     */
    public function normalize(string $mobile): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', trim($mobile)) ?? '';

        if (! preg_match(self::PATTERN, $digits, $matches)) {
            return null;
        }

        return '+639'.$matches[1];
    }

    public function isValid(string $mobile): bool
    {
        return $this->normalize($mobile) !== null;
    }
}
