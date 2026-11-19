<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function isOpen(): bool
    {
        return $this === self::Open || $this === self::Escalated;
    }
}
