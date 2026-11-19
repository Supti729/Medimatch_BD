<?php

namespace App\Support;

use App\Models\User;

/**
 * Finds an account by what the user typed in an "Email or phone number" field.
 */
class AccountLookup
{
    /**
     * @return array{column: 'email'|'phone', value: string}|null  null when the input is neither a valid email nor a valid phone number
     */
    public static function credentialFor(string $input): ?array
    {
        $input = trim($input);

        if (PhoneNumber::looksLikePhone($input)) {
            $phone = PhoneNumber::normalize($input);

            return $phone ? ['column' => 'phone', 'value' => $phone] : null;
        }

        return filter_var($input, FILTER_VALIDATE_EMAIL) ? ['column' => 'email', 'value' => mb_strtolower($input)] : null;
    }

    public static function find(string $input): ?User
    {
        $credential = static::credentialFor($input);

        return $credential ? User::where($credential['column'], $credential['value'])->first() : null;
    }
}
