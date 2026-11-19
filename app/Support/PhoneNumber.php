<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers.
 *
 * Accepts the common ways people type a number and stores one form:
 *   01712345678, +8801712345678, 8801712345678, 01712-345678, 0171 234 5678
 *   -> 01712345678
 *
 * Valid mobile numbers are 11 digits: 01, then an operator digit 3-9, then 8 digits.
 */
class PhoneNumber
{
    /**
     * The normalised number, or null if it is not a valid Bangladeshi mobile number.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/[\s\-().]/', '', trim($input));

        if (str_starts_with($digits, '+')) {
            $digits = substr($digits, 1);
        }

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2); // 8801712345678 -> 01712345678
        }

        return preg_match('/^01[3-9]\d{8}$/', $digits) ? $digits : null;
    }

    /**
     * Does this look like a phone number attempt rather than an email address?
     */
    public static function looksLikePhone(string $input): bool
    {
        return ! str_contains($input, '@') && preg_match('/^[\s+\d\-().]+$/', trim($input)) === 1;
    }
}
