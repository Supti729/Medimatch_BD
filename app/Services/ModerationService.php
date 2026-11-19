<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\ListingStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\MedicineListing;
use App\Models\Report;
use App\Models\User;
use App\Notifications\SimpleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Decisions taken by administrators. Every decision needs a written reason,
 * is written to the audit log, and the affected person is notified.
 */
class ModerationService
{
    public function __construct(private readonly ReservationService $reservations) {}

    /** approve | reject | block | unblock */
    public function decideListing(MedicineListing $listing, string $decision, string $note): void
    {
        $target = match ($decision) {
            'approve', 'unblock' => ListingStatus::Available,
            'reject' => ListingStatus::Rejected,
            'block' => ListingStatus::Blocked,
            default => throw ValidationException::withMessages(['decision' => 'Choose a valid decision.']),
        };

        $allowedFrom = match ($decision) {
            'approve', 'reject' => [ListingStatus::PendingReview],
            'unblock' => [ListingStatus::Blocked],
            'block' => [ListingStatus::Available, ListingStatus::Requested, ListingStatus::Reserved, ListingStatus::Paused, ListingStatus::Unavailable],
        };

        if (! in_array($listing->status, $allowedFrom, true)) {
            throw ValidationException::withMessages(['decision' => 'That decision is not possible for a listing that is '.str_replace('_', ' ', $listing->status->value).'.']);
        }

        DB::transaction(function () use ($listing, $decision, $target, $note) {
            // Blocking ends any request or reservation in progress.
            if ($decision === 'block') {
                $this->reservations->closeAllFor($listing);
            }

            $listing->transitionTo($target, in_array($decision, ['reject', 'block'], true) ? $note : null);

            $action = ['approve' => 'approved', 'reject' => 'rejected', 'block' => 'blocked', 'unblock' => 'unblocked'][$decision];
            AuditLog::record("listing.{$action}", $listing,
                ucfirst($decision)." listing #{$listing->id} {$listing->brand_name}", ['note' => $note]);

            $listing->owner->notify(new SimpleNotification('listing', match ($decision) {
                'approve' => "{$listing->brand_name} was approved and is now visible",
                'reject' => "{$listing->brand_name} was not approved",
                'block' => "{$listing->brand_name} was removed by a moderator",
                'unblock' => "{$listing->brand_name} is visible again",
            }, $note, route('listings.edit', $listing->id, false)));
        });
    }

    public function suspend(User $user, User $admin, string $note): void
    {
        $this->guardAccountChange($user, $admin);
        if ($user->isSuspended()) {
            throw ValidationException::withMessages(['decision' => 'This account is already suspended.']);
        }

        DB::transaction(function () use ($user, $note) {
            $user->status = AccountStatus::Suspended;
            $user->save();

            // Anyone waiting for this user's listings is released.
            $user->listings()->whereIn('status', ['requested', 'reserved'])->get()
                ->each(fn (MedicineListing $listing) => $this->reservations->closeAllFor($listing));

            AuditLog::record('user.suspended', $user, "Suspended user #{$user->id} {$user->name}", ['note' => $note]);
        });
    }

    public function restore(User $user, User $admin, string $note): void
    {
        $this->guardAccountChange($user, $admin);
        if (! $user->isSuspended()) {
            throw ValidationException::withMessages(['decision' => 'This account is not suspended.']);
        }

        $user->status = AccountStatus::Active;
        $user->save();
        AuditLog::record('user.restored', $user, "Restored user #{$user->id} {$user->name}", ['note' => $note]);
    }

    /** Administrators can make a user a partner or reviewer, or back to a regular user. Never an administrator. */
    public function changeRole(User $user, User $admin, Role $role, string $note): void
    {
        $this->guardAccountChange($user, $admin);
        if ($role === Role::Admin) {
            throw ValidationException::withMessages(['role' => 'Administrator access cannot be given from the admin panel.']);
        }

        $old = $user->role;
        $user->role = $role;
        $user->save();
        AuditLog::record('user.role_changed', $user, "Changed role of user #{$user->id} from {$old->value} to {$role->value}", ['note' => $note]);
    }

    /** resolved | rejected | escalated */
    public function decideReport(Report $report, User $admin, ReportStatus $status, string $note): void
    {
        if (! $report->status->isOpen()) {
            throw ValidationException::withMessages(['decision' => 'This report is already closed.']);
        }
        if ($status === ReportStatus::Open) {
            throw ValidationException::withMessages(['decision' => 'Choose a valid decision.']);
        }

        $report->status = $status;
        $report->handled_by = $admin->id;
        $report->resolution_note = $note;
        $report->resolved_at = $status === ReportStatus::Escalated ? null : now();
        $report->save();

        AuditLog::record("report.{$status->value}", $report, "Report #{$report->id}: {$status->value}", ['note' => $note]);

        if ($status !== ReportStatus::Escalated) {
            // The reporter learns the outcome, not the details of any action taken.
            $report->reporter->notify(new SimpleNotification('report', 'Your report was reviewed',
                $status === ReportStatus::Resolved ? 'Thank you. A moderator took action.' : 'A moderator reviewed it and found no rule was broken.',
                route('notifications.index', [], false)));
        }
    }

    private function guardAccountChange(User $user, User $admin): void
    {
        if ($user->id === $admin->id) {
            throw ValidationException::withMessages(['decision' => 'You cannot change your own account here.']);
        }
        if ($user->role->isStaff()) {
            throw ValidationException::withMessages(['decision' => 'Administrator and reviewer accounts cannot be changed from the admin panel.']);
        }
    }
}
