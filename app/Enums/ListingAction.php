<?php

namespace App\Enums;

/**
 * The status changes an owner can ask for from My Listings.
 * Each action has a target status; the model refuses it if the transition is not allowed.
 * Reserving and completing a handover are added with reservations in push B3.
 */
enum ListingAction: string
{
    case Submit = 'submit';
    case Withdraw = 'withdraw';
    case Pause = 'pause';
    case Reactivate = 'reactivate';
    case MarkUnavailable = 'mark_unavailable';
    case Cancel = 'cancel';

    public function target(): ListingStatus
    {
        return match ($this) {
            self::Submit => ListingStatus::PendingReview,
            self::Withdraw => ListingStatus::Draft,
            self::Pause => ListingStatus::Paused,
            self::Reactivate => ListingStatus::Available,
            self::MarkUnavailable => ListingStatus::Unavailable,
            self::Cancel => ListingStatus::Cancelled,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Submit => 'Submit for review',
            self::Withdraw => 'Withdraw from review',
            self::Pause => 'Pause listing',
            self::Reactivate => 'Reactivate listing',
            self::MarkUnavailable => 'Mark as unavailable',
            self::Cancel => 'Cancel listing',
        };
    }

    public function successMessage(): string
    {
        return match ($this) {
            self::Submit => 'Your listing was submitted. A moderator will review it before it becomes visible.',
            self::Withdraw => 'The listing was withdrawn from review and is a draft again.',
            self::Pause => 'The listing is paused and hidden from search.',
            self::Reactivate => 'The listing is available again.',
            self::MarkUnavailable => 'The listing is marked as unavailable.',
            self::Cancel => 'The listing was cancelled.',
        };
    }

    /**
     * The statuses an OWNER may start this action from.
     *
     * This is deliberately narrower than ListingStatus::TRANSITIONS: for example a moderator may move
     * pending_review -> available (approve), but an owner must never be able to approve their own listing.
     */
    public function allowedFrom(): array
    {
        return match ($this) {
            self::Submit => [ListingStatus::Draft, ListingStatus::Rejected],
            self::Withdraw => [ListingStatus::PendingReview],
            self::Pause => [ListingStatus::Available, ListingStatus::Requested],
            self::Reactivate => [ListingStatus::Paused, ListingStatus::Unavailable],
            self::MarkUnavailable => [ListingStatus::Available],
            self::Cancel => [ListingStatus::Available, ListingStatus::Requested, ListingStatus::Paused, ListingStatus::Unavailable],
        };
    }

    /** Owner actions for a listing in this status, in menu order. */
    public static function availableFor(ListingStatus $status): array
    {
        return array_values(array_filter(self::cases(), fn (self $action) => in_array($status, $action->allowedFrom(), true)
            && $status->canTransitionTo($action->target())));
    }
}
