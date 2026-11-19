<?php

namespace App\Enums;

/**
 * Listing life cycle. A status can only change along the arrows in TRANSITIONS.
 *
 *   draft ──submit──> pending_review ──approve (admin)──> available
 *     ^                 |  └──reject (admin)──> rejected ──resubmit──> pending_review
 *     └───withdraw──────┘
 *   available <──> paused / unavailable,  available ──> requested ──> reserved ──> completed   (B3)
 *   most active states ──cancel──> cancelled,   admin can block / unblock
 */
enum ListingStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Available = 'available';
    case Requested = 'requested';
    case Reserved = 'reserved';
    case Paused = 'paused';
    case Unavailable = 'unavailable';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Blocked = 'blocked';

    /** @var array<string, list<string>> from => allowed targets */
    private const TRANSITIONS = [
        'draft' => ['pending_review'],
        'pending_review' => ['draft', 'available', 'rejected'],
        'available' => ['pending_review', 'paused', 'unavailable', 'requested', 'cancelled', 'blocked'],
        'requested' => ['available', 'reserved', 'paused', 'cancelled', 'blocked'],
        'reserved' => ['available', 'completed', 'blocked'],
        'paused' => ['available', 'pending_review', 'cancelled', 'blocked'],
        'unavailable' => ['available', 'pending_review', 'cancelled', 'blocked'],
        'rejected' => ['draft', 'pending_review'],
        'blocked' => ['available'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::TRANSITIONS[$this->value], true);
    }

    /** Statuses the public can see (browse, search, details). */
    public static function publicStatuses(): array
    {
        return [self::Available, self::Requested, self::Reserved];
    }

    /** Statuses in which the owner may edit the medicine details. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected, self::Available, self::Paused, self::Unavailable], true);
    }

    /** Statuses in which the listing can be deleted outright (it was never public). */
    public function isDeletable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}
