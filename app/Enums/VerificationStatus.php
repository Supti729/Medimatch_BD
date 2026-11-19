<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case ReuploadRequired = 'reupload_required';

    /** Still waiting for a reviewer's decision. */
    public function isWaiting(): bool
    {
        return $this === self::Submitted || $this === self::UnderReview;
    }

    /** Decisions a reviewer can make. */
    public static function decisions(): array
    {
        return [self::Verified, self::ReuploadRequired, self::Rejected];
    }
}
