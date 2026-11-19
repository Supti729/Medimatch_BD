<?php

namespace App\Enums;

/**
 * Account roles. A user has exactly one role.
 * One "user" account can both request and donate medicines.
 */
enum Role: string
{
    case User = 'user';
    case Partner = 'partner';
    case Reviewer = 'reviewer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Partner => 'Partner',
            self::Reviewer => 'Authorized reviewer',
            self::Admin => 'Administrator',
        };
    }

    /** Roles that may open the administration panel. */
    public function isStaff(): bool
    {
        return $this === self::Admin || $this === self::Reviewer;
    }
}
