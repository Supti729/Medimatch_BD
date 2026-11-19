<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\ReservationStatus;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All the rules for asking for a listing and handing it over.
 *
 *   request()  requester asks for an available listing               listing: available -> requested
 *   accept()   donor picks one requester; others are declined         listing: requested -> reserved
 *   decline()  donor says no to one requester                        listing: back to available if nobody else is waiting
 *   cancel()   either side calls off a pending or accepted reservation listing: back to available or requested
 *   complete() either side confirms the handover                     listing and request: completed
 *
 * Every method runs in a database transaction and locks the listing row,
 * so two people can never reserve the same listing at the same time.
 * Errors are thrown as ValidationException so the page can show a clear message.
 */
class ReservationService
{
    public function request(MedicineListing $listing, User $requester, ?string $message = null): Reservation
    {
        return DB::transaction(function () use ($listing, $requester, $message) {
            $listing = MedicineListing::with('medicine')->lockForUpdate()->findOrFail($listing->id);

            $this->fail($listing->user_id === $requester->id, 'You cannot request your own listing.');
            $this->fail(! in_array($listing->status, [ListingStatus::Available, ListingStatus::Requested], true), 'This listing can no longer be requested.');
            $this->fail(
                $listing->reservations()->active()->where('requester_id', $requester->id)->exists(),
                'You have already requested this listing.',
            );

            // Link the requester's open request for the same medicine, if there is one.
            $request = $requester->medicineRequests()
                ->where('medicine_id', $listing->medicine_id)
                ->where('status', RequestStatus::Active->value)
                ->latest()
                ->first();

            // Prescription medicines need a request, because that is where the prescription check happens.
            $this->fail(
                $listing->medicine->requires_prescription && ! $request,
                'This is a prescription medicine. Post a request for it first, so your prescription can be checked.',
            );

            $reservation = new Reservation(['message' => $message]);
            $reservation->medicine_listing_id = $listing->id;
            $reservation->requester_id = $requester->id;
            $reservation->medicine_request_id = $request?->id;
            $reservation->save();

            if ($listing->status === ListingStatus::Available) {
                $listing->transitionTo(ListingStatus::Requested);
            }

            return $reservation;
        });
    }

    public function accept(Reservation $reservation, User $donor): void
    {
        DB::transaction(function () use ($reservation, $donor) {
            [$reservation, $listing] = $this->lock($reservation);

            $this->fail(! $reservation->isDonor($donor), 'Only the donor can accept a request.', 403);
            $this->fail($reservation->status !== ReservationStatus::Pending, 'This request is no longer waiting for an answer.');
            $this->fail($listing->status !== ListingStatus::Requested, 'This listing cannot be reserved right now.');

            $reservation->status = ReservationStatus::Accepted;
            $reservation->decided_at = now();
            $reservation->save();

            $listing->transitionTo(ListingStatus::Reserved);

            // Everyone else waiting for this listing is told no.
            $listing->reservations()->where('status', ReservationStatus::Pending->value)->where('id', '!=', $reservation->id)
                ->get()->each(fn (Reservation $other) => $this->close($other, ReservationStatus::Declined));

            if ($request = $reservation->request) {
                $request->moveTo($listing->medicine->requires_prescription ? RequestStatus::VerificationPending : RequestStatus::Reserved);

                // The requester only needs one medicine: withdraw their other pending reservations for this request.
                $request->reservations()->where('status', ReservationStatus::Pending->value)->where('id', '!=', $reservation->id)
                    ->get()->each(fn (Reservation $other) => $this->close($other, ReservationStatus::Cancelled, $reservation->requester_id));
            }
        });
    }

    public function decline(Reservation $reservation, User $donor): void
    {
        DB::transaction(function () use ($reservation, $donor) {
            [$reservation] = $this->lock($reservation);

            $this->fail(! $reservation->isDonor($donor), 'Only the donor can decline a request.', 403);
            $this->fail($reservation->status !== ReservationStatus::Pending, 'This request is no longer waiting for an answer.');

            $this->close($reservation, ReservationStatus::Declined);
        });
    }

    public function cancel(Reservation $reservation, User $actor): void
    {
        DB::transaction(function () use ($reservation, $actor) {
            [$reservation] = $this->lock($reservation);

            $this->fail(! $reservation->isDonor($actor) && $reservation->requester_id !== $actor->id, 'You are not part of this reservation.', 403);
            $this->fail(! $reservation->status->isActive(), 'This reservation is already closed.');

            $this->close($reservation, ReservationStatus::Cancelled, $actor->id);
        });
    }

    public function complete(Reservation $reservation, User $actor): void
    {
        DB::transaction(function () use ($reservation, $actor) {
            [$reservation, $listing] = $this->lock($reservation);

            $this->fail(! $reservation->isDonor($actor) && $reservation->requester_id !== $actor->id, 'You are not part of this reservation.', 403);
            $this->fail($reservation->status !== ReservationStatus::Accepted, 'Only a reserved medicine can be marked as handed over.');
            $this->fail(
                $listing->medicine->requires_prescription && $reservation->request?->status !== RequestStatus::Reserved,
                'This prescription medicine cannot be handed over until the prescription check is approved.',
            );

            $reservation->status = ReservationStatus::Completed;
            $reservation->completed_at = now();
            $reservation->save();

            $listing->transitionTo(ListingStatus::Completed);
            $reservation->request?->moveTo(RequestStatus::Completed);
        });
    }

    /**
     * When the donor pauses, cancels or hides a listing, nobody can still be waiting for it.
     */
    public function closeAllFor(MedicineListing $listing): void
    {
        $listing->reservations()->active()->get()
            ->each(fn (Reservation $reservation) => $this->close($reservation, ReservationStatus::Declined, syncListing: false));
    }

    // ----- Internals -----

    /**
     * End a pending or accepted reservation and put the listing and request back in the right state.
     */
    private function close(Reservation $reservation, ReservationStatus $status, ?int $by = null, bool $syncListing = true): void
    {
        $wasAccepted = $reservation->status === ReservationStatus::Accepted;

        $reservation->status = $status;
        $reservation->decided_at ??= now();
        $reservation->cancelled_by = $status === ReservationStatus::Cancelled ? $by : null;
        $reservation->save();

        $reservation->request?->moveTo(RequestStatus::Active);

        if (! $syncListing) {
            return;
        }

        $listing = $reservation->listing;
        $stillWaiting = $listing->reservations()->where('status', ReservationStatus::Pending->value)->exists();

        if ($wasAccepted && $listing->status === ListingStatus::Reserved) {
            // The reserved medicine is free again (reserved -> available).
            $listing->transitionTo(ListingStatus::Available);
            if ($stillWaiting) {
                $listing->transitionTo(ListingStatus::Requested);
            }
        } elseif ($listing->status === ListingStatus::Requested && ! $stillWaiting) {
            $listing->transitionTo(ListingStatus::Available);
        }
    }

    /** Reload the reservation and lock its listing row for the rest of the transaction. */
    private function lock(Reservation $reservation): array
    {
        $listing = MedicineListing::with('medicine')->lockForUpdate()->findOrFail($reservation->medicine_listing_id);
        $reservation = Reservation::with('request')->findOrFail($reservation->id);
        $reservation->setRelation('listing', $listing);

        return [$reservation, $listing];
    }

    private function fail(bool $condition, string $message, int $status = 422): void
    {
        if (! $condition) {
            return;
        }

        if ($status === 403) {
            abort(403, $message);
        }

        throw ValidationException::withMessages(['reservation' => $message]);
    }
}
