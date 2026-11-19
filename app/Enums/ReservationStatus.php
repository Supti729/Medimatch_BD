<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';     // requester asked, donor has not answered
    case Accepted = 'accepted';   // donor agreed; the listing is reserved for this requester
    case Declined = 'declined';   // donor said no, or chose someone else
    case Cancelled = 'cancelled'; // either side called it off
    case Completed = 'completed'; // handover done

    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Accepted;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for donor',
            self::Accepted => 'Reserved',
            self::Declined => 'Declined',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}
