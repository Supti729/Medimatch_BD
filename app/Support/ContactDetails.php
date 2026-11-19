<?php

namespace App\Support;

/**
 * Spots phone numbers and email addresses in free text.
 *
 * Medimatch BD keeps contact details private, and handovers are arranged in-app,
 * so messages that contain them are refused with a friendly explanation.
 */
class ContactDetails
{
    public static function found(string $text): bool
    {
        // Email addresses
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text)) {
            return true;
        }

        // Bangladeshi mobile numbers, also when written with spaces, dashes or dots: 017 1234 5678, +880-1712-345678
        $digitsOnly = preg_replace('/(?<=\d)[\s\-.]+(?=\d)/', '', $text);

        return (bool) preg_match('/(?:\+?880)?0?1[3-9]\d{8}/', $digitsOnly);
    }
}
