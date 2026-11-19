<?php

namespace App\Enums;

/**
 * Medicine request life cycle.
 *
 *   active ──(donor accepts a reservation)──> reserved ──(handover)──> completed
 *     │                        └─ prescription medicine ─> verification_pending ──(verified, B5)──> reserved
 *     ├──(owner cancels)──> cancelled
 *     └──(no match before expires_at)──> expired
 *   A cancelled or declined reservation puts a reserved request back to active.
 */
enum RequestStatus: string
{
    case Active = 'active';
    case VerificationPending = 'verification_pending';
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    private const TRANSITIONS = [
        'active' => ['reserved', 'verification_pending', 'cancelled', 'expired'],
        'verification_pending' => ['reserved', 'active', 'cancelled'],
        'reserved' => ['completed', 'active', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::TRANSITIONS[$this->value], true);
    }

    /** Still looking for, or waiting on, a medicine. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Active, self::VerificationPending, self::Reserved], true);
    }

    /** Statuses the owner may still edit. */
    public function isEditable(): bool
    {
        return $this === self::Active;
    }
}
