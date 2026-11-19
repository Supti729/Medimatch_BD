<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\ReservationStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\MedicineRequest;
use App\Models\User;
use App\Models\VerificationSubmission;
use App\Notifications\SimpleNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Prescription checks.
 *
 *   submit()      the requester uploads a prescription for one of their prescription-medicine requests
 *   startReview() a reviewer opens it: submitted -> under_review
 *   decide()      verified | reupload_required | rejected, with a note the requester sees
 *   document()    streams the file to an authorized reviewer, and logs that it was viewed
 *
 * The donor never sees the document; they are only told whether the check passed.
 * AI is never used to approve or reject prescriptions.
 */
class VerificationService
{
    private const DISK = 'local'; // storage/app/private: not reachable by any public URL

    public function submit(MedicineRequest $request, User $user, UploadedFile $file): VerificationSubmission
    {
        abort_unless($request->user_id === $user->id, 403);

        $request->loadMissing('medicine');
        if (! $request->medicine->requires_prescription || ! $request->status->isOpen()) {
            throw ValidationException::withMessages(['medicine_request_id' => 'This request does not need a prescription check.']);
        }
        if ($request->verifications()->whereIn('status', ['submitted', 'under_review', 'verified'])->exists()) {
            throw ValidationException::withMessages(['medicine_request_id' => 'A prescription for this request is already being checked or was accepted.']);
        }

        return DB::transaction(function () use ($request, $user, $file) {
            $submission = new VerificationSubmission;
            $submission->user_id = $user->id;
            $submission->medicine_request_id = $request->id;
            $submission->document_path = $file->store("verification/{$user->id}", self::DISK); // random file name
            $submission->mime_type = $file->getMimeType();
            $submission->size_kb = (int) ceil($file->getSize() / 1024);
            $submission->status = VerificationStatus::Submitted;
            $submission->save();

            $this->event($submission, $user, VerificationStatus::Submitted, null);
            AuditLog::record('verification.submitted', $submission, "Prescription uploaded for request #{$request->id}");

            return $submission;
        });
    }

    public function startReview(VerificationSubmission $submission, User $reviewer): void
    {
        if ($submission->status !== VerificationStatus::Submitted) {
            return;
        }

        $submission->status = VerificationStatus::UnderReview;
        $submission->reviewer_id = $reviewer->id;
        $submission->save();

        $this->event($submission, $reviewer, VerificationStatus::UnderReview, null);
        AuditLog::record('verification.review_started', $submission, "Started reviewing verification #{$submission->id}");
    }

    public function decide(VerificationSubmission $submission, User $reviewer, VerificationStatus $decision, string $note): void
    {
        if (! in_array($decision, VerificationStatus::decisions(), true)) {
            throw ValidationException::withMessages(['decision' => 'Choose a valid decision.']);
        }
        if (! $submission->status->isWaiting()) {
            throw ValidationException::withMessages(['decision' => 'This submission has already been decided.']);
        }

        DB::transaction(function () use ($submission, $reviewer, $decision, $note) {
            $submission->status = $decision;
            $submission->reviewer_id = $reviewer->id;
            $submission->reviewer_note = $note;
            $submission->decided_at = now();
            $submission->save();

            $this->event($submission, $reviewer, $decision, $note);
            AuditLog::record("verification.{$decision->value}", $submission, "Verification #{$submission->id}: {$decision->value}", ['note' => $note]);

            $request = $submission->request()->with(['medicine', 'reservations.listing.owner'])->first();
            $medicine = $request->medicine->generic_name.' '.$request->medicine->strength;

            $submission->user->notify(new SimpleNotification('verification', match ($decision) {
                VerificationStatus::Verified => "Your prescription for {$medicine} was accepted",
                VerificationStatus::ReuploadRequired => "Please upload a clearer prescription for {$medicine}",
                default => "Your prescription for {$medicine} was not accepted",
            }, $note, route('verification.index', [], false)));

            if ($decision === VerificationStatus::Verified && $request->status === RequestStatus::VerificationPending) {
                // The reserved medicine can now be handed over. The donor learns only that the check passed.
                $request->moveTo(RequestStatus::Reserved);

                $reservation = $request->reservations->first(fn ($r) => $r->status === ReservationStatus::Accepted);
                $reservation?->listing->owner->notify(new SimpleNotification(
                    'verification',
                    'Prescription check passed for '.$reservation->listing->brand_name,
                    'You can now hand the medicine over. The prescription itself is never shared with you.',
                    route('listings.edit', $reservation->medicine_listing_id, false),
                ));
            }
        });
    }

    /** Stream the document to a reviewer. Every view is written to the audit log. */
    public function document(VerificationSubmission $submission): StreamedResponse
    {
        AuditLog::record('verification.document_viewed', $submission, "Viewed the document of verification #{$submission->id}");

        return Storage::disk(self::DISK)->response($submission->document_path, 'prescription-'.$submission->id, [
            'Content-Type' => $submission->mime_type,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function event(VerificationSubmission $submission, User $actor, VerificationStatus $status, ?string $note): void
    {
        $event = $submission->events()->make();
        $event->actor_id = $actor->id;
        $event->status = $status->value;
        $event->note = $note;
        $event->save();
    }
}
