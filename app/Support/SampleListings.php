<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * TEMPORARY sample data for the front-end part of the project.
 *
 * These listings are invented so the Browse and Medicine Details pages can be
 * designed and demonstrated before the database exists. Every page that uses
 * them shows a "sample data" notice.
 *
 * Backend pushes B2/B3 replace this class with real Eloquent queries on the
 * medicine_listings table. The views read listings with array syntax
 * ($listing['name']), which also works with Eloquent models.
 */
class SampleListings
{
    /**
     * Every sample listing, including ones the public must never see.
     */
    public static function all(): Collection
    {
        $rows = [
            // id, name, generic, strength, form, qty, unit, expiry, type, status, division, district, area, days ago, prescription?, manufacturer
            [1, 'Napa', 'Paracetamol', '500 mg', 'Tablet', 20, 'tablets', '2027-08', 'donation', 'available', 'Dhaka', 'Dhaka', 'Dhanmondi', 1, false, 'Beximco Pharmaceuticals'],
            [2, 'Ace', 'Paracetamol', '500 mg', 'Tablet', 30, 'tablets', '2027-11', 'donation', 'available', 'Chattogram', 'Chattogram', 'Panchlaish', 2, false, 'Square Pharmaceuticals'],
            [3, 'Napa Syrup', 'Paracetamol', '120 mg / 5 ml', 'Syrup', 1, 'bottle (sealed)', '2027-05', 'donation', 'available', 'Dhaka', 'Gazipur', 'Tongi', 3, false, 'Beximco Pharmaceuticals'],
            [4, 'Seclo', 'Omeprazole', '20 mg', 'Capsule', 28, 'capsules', '2027-09', 'donation', 'available', 'Dhaka', 'Dhaka', 'Mirpur', 3, false, 'Square Pharmaceuticals'],
            [5, 'Sergel', 'Esomeprazole', '20 mg', 'Capsule', 14, 'capsules', '2027-03', 'affordable_access', 'available', 'Sylhet', 'Sylhet', 'Sylhet Sadar', 4, false, 'Healthcare Pharmaceuticals'],
            [6, 'Maxpro', 'Esomeprazole', '20 mg', 'Tablet', 20, 'tablets', '2028-01', 'donation', 'reserved', 'Khulna', 'Khulna', 'Sonadanga', 5, false, 'Renata'],
            [7, 'Fexo', 'Fexofenadine', '120 mg', 'Tablet', 10, 'tablets', '2027-06', 'donation', 'available', 'Rajshahi', 'Rajshahi', 'Boalia', 5, false, 'Square Pharmaceuticals'],
            [8, 'Alatrol', 'Cetirizine', '10 mg', 'Tablet', 20, 'tablets', '2027-12', 'donation', 'available', 'Dhaka', 'Narayanganj', 'Narayanganj Sadar', 6, false, 'Square Pharmaceuticals'],
            [9, 'Monas', 'Montelukast', '10 mg', 'Tablet', 15, 'tablets', '2027-04', 'affordable_access', 'available', 'Chattogram', 'Cumilla', 'Cumilla Sadar', 7, true, 'ACME Laboratories'],
            [10, 'Amdocal', 'Amlodipine', '5 mg', 'Tablet', 30, 'tablets', '2027-10', 'donation', 'available', 'Mymensingh', 'Mymensingh', 'Mymensingh Sadar', 8, true, 'Beximco Pharmaceuticals'],
            [11, 'Osartil', 'Losartan Potassium', '50 mg', 'Tablet', 30, 'tablets', '2027-07', 'donation', 'requested', 'Rangpur', 'Rangpur', 'Rangpur Sadar', 9, true, 'Incepta Pharmaceuticals'],
            [12, 'Comet', 'Metformin', '500 mg', 'Tablet', 40, 'tablets', '2028-02', 'donation', 'available', 'Barishal', 'Barishal', 'Barishal Sadar', 10, true, 'Square Pharmaceuticals'],
            [13, 'ORSaline-N', 'Oral Rehydration Salts', '10.25 g', 'Sachet', 12, 'sachets', '2028-04', 'donation', 'available', 'Dhaka', 'Dhaka', 'Uttara', 11, false, 'SMC'],
            [14, 'Tofen', 'Ketotifen', '1 mg', 'Tablet', 20, 'tablets', '2027-02', 'affordable_access', 'available', 'Khulna', 'Jashore', 'Jashore Sadar', 12, false, 'Beximco Pharmaceuticals'],
            [15, 'Ace Plus', 'Paracetamol + Caffeine', '500 mg + 65 mg', 'Tablet', 10, 'tablets', '2027-09', 'donation', 'available', 'Sylhet', 'Moulvibazar', 'Sreemangal', 14, false, 'Square Pharmaceuticals'],
            [16, 'Losectil', 'Omeprazole', '20 mg', 'Capsule', 20, 'capsules', '2027-08', 'donation', 'available', 'Rajshahi', 'Bogura', 'Bogura Sadar', 15, false, 'Eskayef Pharmaceuticals'],

            // Not public: these exist to prove that search never returns them.
            [17, 'Napa Extend', 'Paracetamol', '665 mg', 'Tablet', 10, 'tablets', '2027-08', 'donation', 'blocked', 'Dhaka', 'Dhaka', 'Gulshan', 2, false, 'Beximco Pharmaceuticals'],
            [18, 'Seclo', 'Omeprazole', '40 mg', 'Capsule', 10, 'capsules', '2027-08', 'donation', 'pending_review', 'Dhaka', 'Dhaka', 'Banani', 1, false, 'Square Pharmaceuticals'],
        ];

        $donors = ['Sample donor A', 'Sample donor B', 'Sample donor C', 'Sample donor D'];

        return collect($rows)->map(fn (array $r) => [
            'id' => $r[0],
            'name' => $r[1],
            'generic_name' => $r[2],
            'strength' => $r[3],
            'dosage_form' => $r[4],
            'quantity' => $r[5],
            'unit' => $r[6],
            'expiry' => $r[7],
            'type' => $r[8],
            'status' => $r[9],
            'division' => $r[10],
            'district' => $r[11],
            'area' => $r[12],
            'posted_at' => now()->startOfDay()->subDays($r[13]),
            'requires_prescription' => $r[14],
            'manufacturer' => $r[15],
            'packaging' => 'Original packaging, unopened',
            'donor' => [
                'name' => $donors[$r[0] % count($donors)],
                'joined' => now()->subMonths(2 + $r[0])->startOfMonth(),
                'verified' => $r[0] % 3 !== 0,
            ],
        ]);
    }

    /**
     * Listings that guests are allowed to see.
     */
    public static function visible(): Collection
    {
        return static::all()
            ->whereIn('status', config('medimatch.public_listing_statuses'))
            ->values();
    }

    /**
     * One public listing, or null when it does not exist or is not public.
     */
    public static function find(int $id): ?array
    {
        return static::visible()->firstWhere('id', $id);
    }

    /**
     * Filter and sort the public listings.
     *
     * @param  array{q?: string, division?: string, district?: string, type?: string, form?: string, strength?: string, available?: bool, sort?: string}  $filters
     */
    public static function search(array $filters): Collection
    {
        $words = Str::of($filters['q'] ?? '')->lower()->squish()->explode(' ')->filter();
        $strength = Str::of($filters['strength'] ?? '')->lower()->replace(' ', '')->toString();

        $results = static::visible()
            ->when($words->isNotEmpty(), fn (Collection $list) => $list->filter(function (array $listing) use ($words) {
                $haystack = Str::lower($listing['name'].' '.$listing['generic_name'].' '.$listing['manufacturer'].' '.$listing['strength'].' '.$listing['dosage_form']);
                $compact = str_replace(' ', '', $haystack);

                // Every word must appear somewhere ("napa 500mg" matches "Napa ... 500 mg").
                return $words->every(fn (string $word) => str_contains($haystack, $word) || str_contains($compact, $word));
            }))
            ->when($filters['division'] ?? null, fn (Collection $list, string $v) => $list->where('division', $v))
            ->when($filters['district'] ?? null, fn (Collection $list, string $v) => $list->where('district', $v))
            ->when($filters['type'] ?? null, fn (Collection $list, string $v) => $list->where('type', $v))
            ->when($filters['form'] ?? null, fn (Collection $list, string $v) => $list->where('dosage_form', $v))
            ->when($strength !== '', fn (Collection $list) => $list->filter(
                fn (array $listing) => str_contains(str_replace(' ', '', Str::lower($listing['strength'])), $strength)
            ))
            ->when($filters['available'] ?? false, fn (Collection $list) => $list->where('status', 'available'));

        return match ($filters['sort'] ?? 'newest') {
            'expiry' => $results->sortByDesc('expiry')->values(),
            'name' => $results->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            default => $results->sortByDesc('posted_at')->values(),
        };
    }

    /**
     * Other public listings with the same generic medicine.
     */
    public static function similarTo(array $listing, int $limit = 3): Collection
    {
        return static::visible()
            ->where('generic_name', $listing['generic_name'])
            ->where('id', '!=', $listing['id'])
            ->take($limit)
            ->values();
    }
}
