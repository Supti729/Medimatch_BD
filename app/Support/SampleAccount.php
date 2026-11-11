<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * TEMPORARY sample data for the signed-in pages (front-end part).
 *
 * It describes one invented account so the dashboard, listings, requests,
 * messages, notifications, history and verification pages can be designed and
 * demonstrated before authentication and the database exist.
 *
 * Every page that uses it shows a "design preview" notice.
 * The backend part replaces each method with queries for the logged-in user.
 */
class SampleAccount
{
    public static function user(): array
    {
        return [
            'name' => 'Preview User',
            'email' => 'preview.user@example.com',
            'phone' => '01700000000',
            'division' => 'Dhaka',
            'district' => 'Dhaka',
            'upazila' => 'Dhanmondi',
            'joined' => now()->subMonths(5)->startOfMonth(),
            'verified' => true,
            'privacy' => [
                'show_avatar' => true,
                'show_contributions' => true,
                'show_verified_badge' => true,
                'matched_messages_only' => true,
            ],
        ];
    }

    /**
     * Medicines this account has offered, in every stage of the listing workflow.
     */
    public static function listings(): Collection
    {
        $rows = [
            // id, name, generic, strength, form, qty, unit, expiry, status, days ago, people requesting, note
            [101, 'Napa', 'Paracetamol', '500 mg', 'Tablet', 20, 'tablets', '2027-08', 'available', 1, 2, null],
            [102, 'Seclo', 'Omeprazole', '20 mg', 'Capsule', 14, 'capsules', '2027-09', 'pending_review', 0, 0, null],
            [103, 'Fexo', 'Fexofenadine', '120 mg', 'Tablet', 10, 'tablets', '2027-06', 'reserved', 6, 1, null],
            [104, 'Alatrol', 'Cetirizine', '10 mg', 'Tablet', 10, 'tablets', '2027-12', 'draft', 0, 0, null],
            [105, 'Ace', 'Paracetamol', '500 mg', 'Tablet', 30, 'tablets', '2027-11', 'completed', 24, 1, null],
            [106, 'Tofen', 'Ketotifen', '1 mg', 'Tablet', 8, 'tablets', '2026-12', 'rejected', 12, 0, 'The expiry date is too close. Listings need at least three months of shelf life.'],
        ];

        $user = static::user();

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'name' => $r[1],
            'generic_name' => $r[2],
            'strength' => $r[3],
            'dosage_form' => $r[4],
            'quantity' => $r[5],
            'unit' => $r[6],
            'expiry' => $r[7],
            'status' => $r[8],
            'posted_at' => now()->startOfDay()->subDays($r[9]),
            'requests_count' => $r[10],
            'review_note' => $r[11],
            'type' => 'donation',
            'batch_number' => null,
            'packaging' => 'sealed_box',
            'notes' => null,
            'division' => $user['division'],
            'district' => $user['district'],
            'area' => $user['upazila'],
        ]);
    }

    public static function findListing(int $id): ?array
    {
        return static::listings()->firstWhere('id', $id);
    }

    /**
     * Medicines this account has asked for.
     */
    public static function requests(): Collection
    {
        $rows = [
            // id, medicine, generic, strength, form, qty, unit, status, days ago, preference, needs prescription
            [201, 'Paracetamol', 'Paracetamol', '500 mg', 'Tablet', 20, 'tablets', 'match_found', 2, 'donation', false],
            [202, 'Amdocal', 'Amlodipine', '5 mg', 'Tablet', 30, 'tablets', 'verification_pending', 4, 'either', true],
            [203, 'Comet', 'Metformin', '500 mg', 'Tablet', 60, 'tablets', 'active', 1, 'either', true],
            [204, 'ORSaline-N', 'Oral Rehydration Salts', '10.25 g', 'Sachet', 10, 'sachets', 'completed', 31, 'donation', false],
            [205, 'Monas', 'Montelukast', '10 mg', 'Tablet', 15, 'tablets', 'cancelled', 40, 'either', true],
        ];

        $user = static::user();

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'name' => $r[1],
            'generic_name' => $r[2],
            'strength' => $r[3],
            'dosage_form' => $r[4],
            'quantity' => $r[5],
            'unit' => $r[6],
            'status' => $r[7],
            'created_at' => now()->startOfDay()->subDays($r[8]),
            'preference' => $r[9],
            'requires_prescription' => $r[10],
            'notes' => null,
            'division' => $user['division'],
            'district' => $user['district'],
            'area' => $user['upazila'],
        ]);
    }

    public static function findRequest(int $id): ?array
    {
        return static::requests()->firstWhere('id', $id);
    }

    /**
     * Public listings that could fulfil a request.
     *
     * Identity rules come first: the generic medicine, strength and dosage form
     * must all agree. This is a plain rule-based match on sample data; the AI
     * ranking service (Part 3) will only re-order results that pass these rules.
     */
    public static function matchesFor(array $request): Collection
    {
        $normalise = fn (string $value) => strtolower(str_replace(' ', '', $value));

        return SampleListings::visible()
            ->where('status', 'available')
            ->filter(fn (array $listing) => $normalise($listing['generic_name']) === $normalise($request['generic_name'])
                && $normalise($listing['strength']) === $normalise($request['strength'])
                && $listing['dosage_form'] === $request['dosage_form'])
            // Same district first, then same division, then the rest.
            ->sortBy(fn (array $listing) => match (true) {
                $listing['district'] === $request['district'] => 0,
                $listing['division'] === $request['division'] => 1,
                default => 2,
            })
            ->values();
    }

    public static function saved(): Collection
    {
        return SampleListings::visible()->whereIn('id', [2, 4, 13, 6])->values();
    }

    /**
     * Private conversations. "mine" marks messages written by this account.
     */
    public static function conversations(): Collection
    {
        return collect([
            [
                'id' => 301,
                'with' => 'Sample donor B',
                'about' => ['type' => 'request', 'label' => 'Your request: Paracetamol 500 mg', 'route' => ['requests.show', 201]],
                'status' => 'match_found',
                'unread' => 2,
                'messages' => [
                    [false, 'Hello, I saw your request. I have a sealed strip of Napa 500 mg, 20 tablets.', 26 * 60],
                    [true, 'Thank you. The expiry date is fine for me. Where would be convenient to meet?', 25 * 60],
                    [false, 'I can meet outside the pharmacy near Dhanmondi 27 tomorrow afternoon.', 95],
                    [false, 'Does 4 pm work for you?', 90],
                ],
            ],
            [
                'id' => 302,
                'with' => 'Sample requester K',
                'about' => ['type' => 'listing', 'label' => 'Your listing: Fexo 120 mg', 'route' => ['listings.edit', 103]],
                'status' => 'reserved',
                'unread' => 0,
                'messages' => [
                    [false, 'Hi, is the Fexo 120 mg still available?', 6 * 24 * 60],
                    [true, 'Yes, it is sealed and expires in June 2027. I have reserved it for you.', 6 * 24 * 60 - 30],
                    [false, 'Thank you. I will confirm a time this week.', 5 * 24 * 60],
                ],
            ],
            [
                'id' => 303,
                'with' => 'Sample requester M',
                'about' => ['type' => 'listing', 'label' => 'Your listing: Ace 500 mg', 'route' => ['listings.edit', 105]],
                'status' => 'completed',
                'unread' => 0,
                'messages' => [
                    [true, 'It was good to meet you. I have marked the handover as completed.', 20 * 24 * 60],
                    [false, 'Received, thank you very much.', 20 * 24 * 60 - 10],
                ],
            ],
        ])->map(function (array $conversation) {
            $conversation['messages'] = collect($conversation['messages'])->map(fn (array $m) => [
                'mine' => $m[0],
                'body' => $m[1],
                'sent_at' => now()->subMinutes($m[2]),
            ]);
            $conversation['last'] = $conversation['messages']->last();

            return $conversation;
        });
    }

    public static function findConversation(int $id): ?array
    {
        return static::conversations()->firstWhere('id', $id);
    }

    public static function notifications(): Collection
    {
        $rows = [
            // type, title, text, minutes ago, read?, route
            ['message', 'New message from Sample donor B', 'Does 4 pm work for you?', 90, false, ['messages.show', 301]],
            ['match', 'A match was found for your request', 'Napa 500 mg in Dhanmondi matches your request for Paracetamol 500 mg.', 3 * 60, false, ['requests.show', 201]],
            ['verification', 'Your prescription is under review', 'An authorized reviewer is checking the document for your Amlodipine 5 mg request.', 26 * 60, false, ['verification.index']],
            ['request', 'Someone requested your medicine', 'There are 2 requests for your listing Napa 500 mg.', 30 * 60, true, ['listings.index']],
            ['reservation', 'Fexo 120 mg was reserved', 'You reserved this listing for Sample requester K.', 6 * 24 * 60, true, ['listings.index']],
            ['listing', 'A listing was not approved', 'Tofen 1 mg was rejected: the expiry date is too close.', 12 * 24 * 60, true, ['listings.edit', 106]],
            ['completed', 'Handover completed', 'Ace 500 mg was marked as completed. Thank you for donating.', 20 * 24 * 60, true, ['history.index']],
        ];

        return collect($rows)->map(fn (array $r, int $i) => [
            'id' => 401 + $i,
            'type' => $r[0],
            'title' => $r[1],
            'text' => $r[2],
            'created_at' => now()->subMinutes($r[3]),
            'read' => $r[4],
            'route' => $r[5],
        ]);
    }

    /**
     * Finished and cancelled interactions.
     */
    public static function history(): Collection
    {
        $rows = [
            // role, medicine, other party, status, days ago, area
            ['donated', 'Ace 500 mg · 30 tablets', 'Sample requester M', 'completed', 20, 'Dhanmondi, Dhaka'],
            ['received', 'ORSaline-N · 10 sachets', 'Sample donor D', 'completed', 29, 'Mirpur, Dhaka'],
            ['requested', 'Monas 10 mg · 15 tablets', null, 'cancelled', 38, 'Dhanmondi, Dhaka'],
            ['donated', 'Napa Syrup 120 mg / 5 ml · 1 bottle', 'Sample requester R', 'completed', 62, 'Dhanmondi, Dhaka'],
        ];

        return collect($rows)->map(fn (array $r, int $i) => [
            'id' => 501 + $i,
            'role' => $r[0],
            'medicine' => $r[1],
            'with' => $r[2],
            'status' => $r[3],
            'date' => now()->startOfDay()->subDays($r[4]),
            'area' => $r[5],
        ]);
    }

    /**
     * Verification submissions with their status history.
     */
    public static function verifications(): Collection
    {
        return collect([
            [
                'id' => 601,
                'for' => 'Request: Amlodipine 5 mg · 30 tablets',
                'document' => 'Prescription',
                'status' => 'under_review',
                'note' => null,
                'history' => [
                    ['submitted', 'You uploaded a document.', now()->subHours(30)],
                    ['under_review', 'An authorized reviewer started the review.', now()->subHours(26)],
                ],
            ],
            [
                'id' => 602,
                'for' => 'Request: Montelukast 10 mg · 15 tablets',
                'document' => 'Prescription',
                'status' => 'reupload_required',
                'note' => 'The photo is blurred and the date cannot be read. Please upload a clearer picture.',
                'history' => [
                    ['submitted', 'You uploaded a document.', now()->subDays(41)],
                    ['under_review', 'An authorized reviewer started the review.', now()->subDays(40)],
                    ['reupload_required', 'The reviewer asked for a clearer document.', now()->subDays(40)],
                ],
            ],
        ]);
    }

    /**
     * Numbers for the dashboard, counted from the sample records above
     * (the same way the real dashboard will count database rows).
     */
    public static function stats(): array
    {
        $listings = static::listings();
        $requests = static::requests();

        return [
            'active_listings' => $listings->whereIn('status', ['available', 'requested'])->count(),
            'active_requests' => $requests->whereIn('status', ['active', 'matching', 'match_found'])->count(),
            'pending_verification' => $requests->where('status', 'verification_pending')->count(),
            'reserved' => $listings->where('status', 'reserved')->count() + $requests->where('status', 'reserved')->count(),
            'completed' => static::history()->where('status', 'completed')->count(),
        ];
    }
}
