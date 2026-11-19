<?php

namespace App\Notifications;

use App\Models\Reservation;

/**
 * Everything that happens to a reservation, told to the person who did not do it.
 *
 * event: requested | accepted | declined | cancelled | completed
 */
class ReservationNotification extends AppNotification
{
    public function __construct(public readonly Reservation $reservation, public readonly string $event, public readonly bool $toDonor) {}

    protected function type(): string
    {
        return match ($this->event) {
            'requested' => 'request',
            'completed' => 'completed',
            default => 'reservation',
        };
    }

    private function medicine(): string
    {
        $listing = $this->reservation->listing;

        return $listing->brand_name.' '.$listing->medicine->strength;
    }

    protected function title(): string
    {
        $requester = $this->reservation->requester->name;
        $donor = $this->reservation->listing->owner->name;

        return match ($this->event) {
            'requested' => "{$requester} asked for your {$this->medicine()}",
            'accepted' => "{$donor} reserved {$this->medicine()} for you",
            'declined' => "Your request for {$this->medicine()} was not accepted",
            'cancelled' => $this->toDonor ? "{$requester} cancelled their request for {$this->medicine()}" : "The reservation of {$this->medicine()} was cancelled",
            'completed' => "Handover of {$this->medicine()} completed",
            default => "Update on {$this->medicine()}",
        };
    }

    protected function text(): string
    {
        return match ($this->event) {
            'requested' => 'Accept or decline the request on your listing page.',
            'accepted' => 'Use the in-app messages to agree a safe place and time for the handover.',
            'declined' => 'The donor chose someone else or could not hand it over. Your request is still open for other matches.',
            'cancelled' => $this->toDonor ? 'Your listing is available for others again.' : 'Your request is open again for other matches.',
            'completed' => $this->toDonor ? 'Thank you for donating.' : 'We hope you feel better soon.',
            default => '',
        };
    }

    protected function url(): string
    {
        if ($this->toDonor) {
            return route('listings.edit', $this->reservation->medicine_listing_id, false);
        }

        return $this->reservation->medicine_request_id
            ? route('requests.show', $this->reservation->medicine_request_id, false)
            : route('medicines.show', $this->reservation->medicine_listing_id, false);
    }
}
