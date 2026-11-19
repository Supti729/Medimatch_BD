<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * TEMPORARY sample data for the admin pages (front-end part).
 *
 * Invented users, reports, verification submissions, partners and audit
 * entries so the administration panel can be designed and demonstrated before
 * the database exists. Every admin page shows a "design preview" notice.
 *
 * The backend part (push B5) replaces each method with real queries.
 * All people and organizations here are fictional.
 */
class SampleAdmin
{
    public static function users(): Collection
    {
        $rows = [
            // id, name, district, role, status, joined months ago, listings, requests, reports against
            [1, 'Rahim Uddin', 'Dhaka', 'user', 'active', 5, 4, 1, 0],
            [2, 'Nusrat Jahan', 'Chattogram', 'user', 'active', 5, 2, 3, 0],
            [3, 'Tanvir Hasan', 'Sylhet', 'user', 'active', 4, 1, 0, 0],
            [4, 'Farzana Akter', 'Khulna', 'user', 'active', 4, 3, 2, 1],
            [5, 'Sabbir Hossain', 'Rajshahi', 'user', 'suspended', 3, 2, 0, 3],
            [6, 'Mitu Rani Das', 'Barishal', 'user', 'active', 3, 0, 4, 0],
            [7, 'Arif Chowdhury', 'Dhaka', 'user', 'active', 2, 3, 1, 0],
            [8, 'Shirin Sultana', 'Mymensingh', 'user', 'active', 2, 1, 2, 0],
            [9, 'Imran Kabir', 'Rangpur', 'user', 'active', 1, 2, 0, 1],
            [10, 'Lamia Rahman', 'Dhaka', 'user', 'active', 1, 0, 1, 0],
            [11, 'Jannatul Ferdous', 'Gazipur', 'user', 'active', 0, 0, 2, 0],
            [12, 'Hope Health Foundation', 'Dhaka', 'partner', 'active', 2, 0, 0, 0],
            [13, 'Sample Reviewer', 'Dhaka', 'reviewer', 'active', 6, 0, 0, 0],
            [14, 'Sample Administrator', 'Dhaka', 'admin', 'active', 6, 0, 0, 0],
        ];

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'name' => $r[1],
            'email' => 'user'.$r[0].'@example.com',
            'phone' => '017000000'.str_pad((string) $r[0], 2, '0', STR_PAD_LEFT),
            'district' => $r[2],
            'role' => $r[3],
            'status' => $r[4],
            'joined' => now()->startOfMonth()->subMonths($r[5])->addDays($r[0]),
            'listings_count' => $r[6],
            'requests_count' => $r[7],
            'reports_count' => $r[8],
        ]);
    }

    public static function findUser(int $id): ?array
    {
        return static::users()->firstWhere('id', $id);
    }

    /**
     * Every listing, including the ones hidden from the public, with an owner.
     */
    public static function listings(): Collection
    {
        $owners = static::users()->where('role', 'user')->values();

        return SampleListings::all()->map(function (array $listing) use ($owners) {
            $owner = $owners[$listing['id'] % $owners->count()];

            return $listing + [
                'owner' => ['id' => $owner['id'], 'name' => $owner['name']],
                'reports_count' => $listing['status'] === 'blocked' ? 2 : 0,
            ];
        });
    }

    public static function findListing(int $id): ?array
    {
        return static::listings()->firstWhere('id', $id);
    }

    public static function requests(): Collection
    {
        $rows = [
            // id, user id, medicine, quantity, district, status, days ago, prescription?
            [201, 6, 'Paracetamol 500 mg · Tablet', '20 tablets', 'Barishal', 'match_found', 2, false],
            [202, 2, 'Amlodipine 5 mg · Tablet', '30 tablets', 'Chattogram', 'verification_pending', 4, true],
            [203, 8, 'Metformin 500 mg · Tablet', '60 tablets', 'Mymensingh', 'active', 1, true],
            [204, 6, 'Oral Rehydration Salts · Sachet', '10 sachets', 'Barishal', 'completed', 31, false],
            [205, 4, 'Montelukast 10 mg · Tablet', '15 tablets', 'Khulna', 'cancelled', 40, true],
            [206, 10, 'Omeprazole 20 mg · Capsule', '14 capsules', 'Dhaka', 'matching', 0, false],
            [207, 11, 'Cetirizine 10 mg · Tablet', '10 tablets', 'Gazipur', 'reserved', 3, false],
            [208, 1, 'Losartan Potassium 50 mg · Tablet', '30 tablets', 'Dhaka', 'active', 6, true],
            [209, 2, 'Fexofenadine 120 mg · Tablet', '10 tablets', 'Chattogram', 'expired', 75, false],
        ];

        $users = static::users()->keyBy('id');

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'user' => ['id' => $r[1], 'name' => $users[$r[1]]['name']],
            'medicine' => $r[2],
            'quantity' => $r[3],
            'district' => $r[4],
            'status' => $r[5],
            'created_at' => now()->startOfDay()->subDays($r[6]),
            'requires_prescription' => $r[7],
        ]);
    }

    public static function verifications(): Collection
    {
        $rows = [
            // id, user id, request label, status, hours ago, reviewer, note
            [601, 2, 'Amlodipine 5 mg · 30 tablets', 'under_review', 26, 'Sample Reviewer', null],
            [603, 8, 'Metformin 500 mg · 60 tablets', 'submitted', 5, null, null],
            [604, 1, 'Losartan Potassium 50 mg · 30 tablets', 'submitted', 2, null, null],
            [602, 4, 'Montelukast 10 mg · 15 tablets', 'reupload_required', 40 * 24, 'Sample Reviewer', 'The photo is blurred and the date cannot be read.'],
            [605, 6, 'Salbutamol 100 mcg · Inhaler', 'verified', 50 * 24, 'Sample Reviewer', 'Prescription is valid and matches the request.'],
            [606, 9, 'Amoxicillin 500 mg · Capsule', 'rejected', 60 * 24, 'Sample Reviewer', 'The prescription is more than one year old.'],
        ];

        $users = static::users()->keyBy('id');

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'user' => ['id' => $r[1], 'name' => $users[$r[1]]['name']],
            'request' => $r[2],
            'document' => 'Prescription',
            'status' => $r[3],
            'submitted_at' => now()->subHours($r[4]),
            'reviewer' => $r[5],
            'note' => $r[6],
        ]);
    }

    public static function findVerification(int $id): ?array
    {
        return static::verifications()->firstWhere('id', $id);
    }

    public static function reports(): Collection
    {
        $rows = [
            // id, target type, target label, reason, details, reporter id, reported user id, status, hours ago
            [701, 'listing', 'Napa Extend 665 mg (listing #17)', 'Asking for money for a donation', 'The donor asked me to pay before the handover.', 6, 5, 'open', 6],
            [702, 'listing', 'Napa Extend 665 mg (listing #17)', 'Wrong or misleading details', 'The strip in the photo looks opened.', 8, 5, 'open', 20],
            [703, 'message', 'Message in a conversation about Fexo 120 mg', 'Asked for personal details', 'The other person kept asking for my home address.', 10, 9, 'escalated', 50],
            [704, 'user', 'Account: Sabbir Hossain', 'Suspected reselling', 'This account lists many medicines and asks for payment.', 2, 5, 'resolved', 9 * 24],
            [705, 'listing', 'Alatrol 10 mg (listing #8)', 'Expired medicine', 'I think this is expired.', 11, 4, 'rejected', 14 * 24],
        ];

        $users = static::users()->keyBy('id');

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'target_type' => $r[1],
            'target' => $r[2],
            'reason' => $r[3],
            'details' => $r[4],
            'reporter' => ['id' => $r[5], 'name' => $users[$r[5]]['name']],
            'reported_user' => ['id' => $r[6], 'name' => $users[$r[6]]['name'], 'status' => $users[$r[6]]['status']],
            'status' => $r[7],
            'created_at' => now()->subHours($r[8]),
        ]);
    }

    public static function findReport(int $id): ?array
    {
        return static::reports()->firstWhere('id', $id);
    }

    /**
     * Interactions between a donor and a recipient.
     */
    public static function transactions(): Collection
    {
        $rows = [
            // id, medicine, donor id, recipient id, status, started days ago, months ago completed (null = not completed)
            [801, 'Fexo 120 mg · 10 tablets', 1, 10, 'reserved', 6, null],
            [802, 'Maxpro 20 mg · 20 tablets', 4, 11, 'reserved', 5, null],
            [803, 'Osartil 50 mg · 30 tablets', 9, 1, 'requested', 2, null],
            [804, 'Ace 500 mg · 30 tablets', 7, 6, 'completed', 24, 0],
            [805, 'ORSaline-N · 10 sachets', 2, 6, 'completed', 33, 1],
            [806, 'Napa Syrup · 1 bottle', 1, 8, 'completed', 64, 2],
            [807, 'Seclo 20 mg · 14 capsules', 3, 2, 'completed', 70, 2],
            [808, 'Comet 500 mg · 30 tablets', 7, 4, 'cancelled', 45, null],
            [809, 'Alatrol 10 mg · 10 tablets', 4, 10, 'completed', 100, 3],
        ];

        $users = static::users()->keyBy('id');

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'medicine' => $r[1],
            'donor' => ['id' => $r[2], 'name' => $users[$r[2]]['name']],
            'recipient' => ['id' => $r[3], 'name' => $users[$r[3]]['name']],
            'status' => $r[4],
            'started_at' => now()->startOfDay()->subDays($r[5]),
            'completed_at' => $r[6] === null ? null : now()->startOfMonth()->subMonths($r[6])->addDays(3),
        ]);
    }

    public static function partners(): Collection
    {
        return collect([
            ['id' => 901, 'name' => 'Hope Health Foundation', 'type' => 'NGO', 'district' => 'Dhaka', 'status' => 'verified', 'plan' => 'Community (free)', 'joined' => now()->subMonths(2)],
            ['id' => 902, 'name' => 'Riverside Community Hospital', 'type' => 'Hospital', 'district' => 'Chattogram', 'status' => 'under_review', 'plan' => 'Institution', 'joined' => now()->subDays(9)],
            ['id' => 903, 'name' => 'Green Leaf Pharmacy', 'type' => 'Pharmacy', 'district' => 'Sylhet', 'status' => 'submitted', 'plan' => 'Pharmacy', 'joined' => now()->subDays(2)],
        ]);
    }

    /**
     * Mock organizational plans for the revenue-model demonstration. No payments exist.
     */
    public static function plans(): array
    {
        return [
            ['name' => 'Community', 'price' => 'Free', 'for' => 'NGOs and charities', 'features' => ['Organization profile', 'Donation listings', 'Basic impact report']],
            ['name' => 'Institution', 'price' => 'Mock: BDT 5,000 / month', 'for' => 'Hospitals and clinics', 'features' => ['Everything in Community', 'Multiple staff accounts', 'Detailed impact reports']],
            ['name' => 'Pharmacy', 'price' => 'Mock: BDT 3,000 / month', 'for' => 'Licensed pharmacies', 'features' => ['Affordable-access listings', 'Stock availability alerts', 'Verification badge']],
        ];
    }

    public static function announcements(): Collection
    {
        return collect([
            ['id' => 1001, 'title' => 'Updated safety guidelines', 'message' => 'Please read the updated list of medicines that cannot be listed.', 'audience' => 'All users', 'sent_at' => now()->subDays(8), 'sent_by' => 'Sample Administrator'],
            ['id' => 1002, 'title' => 'Scheduled maintenance', 'message' => 'The platform will be unavailable for one hour on Friday night.', 'audience' => 'All users', 'sent_at' => now()->subDays(21), 'sent_by' => 'Sample Administrator'],
        ]);
    }

    public static function auditLogs(): Collection
    {
        $rows = [
            // actor, action, target, hours ago
            ['Sample Reviewer', 'verification.review_started', 'Verification #601', 26],
            ['Sample Administrator', 'report.escalated', 'Report #703', 30],
            ['Sample Administrator', 'listing.blocked', 'Listing #17 Napa Extend 665 mg', 44],
            ['Sample Administrator', 'listing.approved', 'Listing #1 Napa 500 mg', 48],
            ['Sample Administrator', 'announcement.sent', 'Announcement #1001', 8 * 24],
            ['Sample Administrator', 'user.suspended', 'User #5 Sabbir Hossain', 9 * 24],
            ['Sample Administrator', 'report.resolved', 'Report #704', 9 * 24],
            ['Sample Administrator', 'listing.rejected', 'Listing by user #7 Tofen 1 mg', 12 * 24],
            ['Sample Administrator', 'report.rejected', 'Report #705', 14 * 24],
            ['Sample Reviewer', 'verification.reupload_requested', 'Verification #602', 40 * 24],
            ['Sample Reviewer', 'verification.verified', 'Verification #605', 50 * 24],
            ['Sample Reviewer', 'verification.rejected', 'Verification #606', 60 * 24],
            ['Sample Administrator', 'settings.updated', 'Minimum shelf life: 3 months', 70 * 24],
        ];

        return collect($rows)->map(fn (array $r, int $i) => [
            'id' => 1101 + $i,
            'actor' => $r[0],
            'action' => $r[1],
            'target' => $r[2],
            'at' => now()->subHours($r[3]),
        ]);
    }

    /**
     * Medicine catalogue: what may be listed, and what is restricted.
     */
    public static function medicines(): Collection
    {
        $fromListings = SampleListings::all()->groupBy('generic_name')->map(fn (Collection $group, string $generic) => [
            'generic_name' => $generic,
            'brands' => $group->pluck('name')->unique()->values()->all(),
            'forms' => $group->pluck('dosage_form')->unique()->values()->all(),
            'requires_prescription' => (bool) $group->first()['requires_prescription'],
            'eligibility' => 'allowed',
            'reason' => null,
        ])->values();

        $restricted = collect([
            ['generic_name' => 'Insulin (all types)', 'brands' => [], 'forms' => ['Injection'], 'requires_prescription' => true, 'eligibility' => 'not_allowed', 'reason' => 'Needs refrigeration (cold chain)'],
            ['generic_name' => 'Diazepam', 'brands' => [], 'forms' => ['Tablet'], 'requires_prescription' => true, 'eligibility' => 'not_allowed', 'reason' => 'Controlled medicine'],
            ['generic_name' => 'Morphine', 'brands' => [], 'forms' => ['Tablet', 'Injection'], 'requires_prescription' => true, 'eligibility' => 'not_allowed', 'reason' => 'Controlled medicine'],
        ]);

        return $fromListings->sortBy('generic_name')->values()->concat($restricted);
    }

    public static function settings(): array
    {
        return [
            'min_shelf_life_months' => 3,
            'max_quantity' => 1000,
            'max_photos' => 4,
            'review_listings' => true,
            'prescription_check' => true,
            'allow_affordable_access' => false,
            'request_expiry_days' => 60,
        ];
    }

    /**
     * Dashboard numbers, counted from the sample records above
     * (the real dashboard will count database rows the same way).
     */
    public static function stats(): array
    {
        $users = static::users();
        $listings = static::listings();

        return [
            'total_users' => $users->count(),
            'active_users' => $users->where('status', 'active')->count(),
            'total_listings' => $listings->count(),
            'active_listings' => $listings->whereIn('status', config('medimatch.public_listing_statuses'))->count(),
            'total_requests' => static::requests()->count(),
            'pending_verification' => static::verifications()->whereIn('status', ['submitted', 'under_review'])->count(),
            'open_reports' => static::reports()->whereIn('status', ['open', 'escalated'])->count(),
            'completed' => static::transactions()->where('status', 'completed')->count(),
            'partners' => static::partners()->count(),
        ];
    }

    /**
     * Chart series, each computed from the sample records.
     */
    public static function charts(): array
    {
        // The last six months, oldest first: ['2026-06' => 'Jun', ...]
        $months = collect(range(5, 0))->mapWithKeys(fn (int $back) => [
            now()->startOfMonth()->subMonths($back)->format('Y-m') => now()->startOfMonth()->subMonths($back)->format('M'),
        ]);

        $perMonth = fn (Collection $dates) => $months->mapWithKeys(fn (string $label, string $key) => [
            $label => $dates->filter(fn ($date) => $date && $date->format('Y-m') === $key)->count(),
        ])->all();

        $topDistricts = fn (Collection $rows) => $rows->countBy('district')->sortDesc()->take(6)->all();

        $types = static::listings()->countBy('type');

        return [
            'registrations' => $perMonth(static::users()->pluck('joined')),
            'completed' => $perMonth(static::transactions()->pluck('completed_at')),
            'listings_by_district' => $topDistricts(static::listings()),
            'requests_by_district' => $topDistricts(static::requests()),
            'listing_types' => [
                'Donation' => $types['donation'] ?? 0,
                'Affordable access' => $types['affordable_access'] ?? 0,
            ],
        ];
    }
}
