<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Models\District;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo listings and a demo request for local development and presentations ONLY.
 *
 * Listings normally become public only after a moderator approves them (push B5).
 * So that browsing and matching can be shown before then, these demo listings are created
 * already approved. All donors are clearly named "Demo donor". Refuses to run in production.
 *
 * Safe to run more than once: it does nothing if demo listings already exist.
 */
class DemoListingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoListingSeeder must never run in production.');
        }

        $donors = collect(['A', 'B', 'C'])->map(function (string $letter, int $i) {
            $district = District::where('name', ['Dhaka', 'Chattogram', 'Sylhet'][$i])->first();
            $user = User::firstOrNew(['email' => 'donor'.strtolower($letter).'@medimatch.test']);
            $user->fill([
                'name' => "Demo donor {$letter}",
                'password' => 'password',
                'division_id' => $district->division_id,
                'district_id' => $district->id,
                'upazila_id' => $district->upazilas()->orderBy('name')->value('id'),
            ]);
            $user->email_verified_at = now();
            $user->save();

            return $user;
        });

        if (MedicineListing::whereIn('user_id', $donors->pluck('id'))->exists()) {
            return;
        }

        // brand, generic, strength, form, quantity, unit, months until expiry, district, donor index, days ago
        $rows = [
            ['Napa', 'Paracetamol', '500 mg', 'Tablet', 20, 'tablets', 18, 'Dhaka', 0, 1],
            ['Ace', 'Paracetamol', '500 mg', 'Tablet', 30, 'tablets', 22, 'Chattogram', 1, 2],
            ['Napa', 'Paracetamol', '120 mg / 5 ml', 'Syrup', 1, 'bottles', 14, 'Gazipur', 0, 3],
            ['Seclo', 'Omeprazole', '20 mg', 'Capsule', 28, 'capsules', 20, 'Dhaka', 0, 3],
            ['Sergel', 'Esomeprazole', '20 mg', 'Capsule', 14, 'capsules', 16, 'Sylhet', 2, 4],
            ['Fexo', 'Fexofenadine', '120 mg', 'Tablet', 10, 'tablets', 15, 'Rajshahi', 1, 5],
            ['Alatrol', 'Cetirizine', '10 mg', 'Tablet', 20, 'tablets', 24, 'Narayanganj', 0, 6],
            ['Monas', 'Montelukast', '10 mg', 'Tablet', 15, 'tablets', 13, 'Cumilla', 1, 7],
            ['Amdocal', 'Amlodipine', '5 mg', 'Tablet', 30, 'tablets', 19, 'Mymensingh', 2, 8],
            ['Comet', 'Metformin', '500 mg', 'Tablet', 40, 'tablets', 26, 'Barishal', 2, 10],
            ['ORSaline-N', 'Oral Rehydration Salts', '10.25 g', 'Sachet', 12, 'sachets', 30, 'Dhaka', 0, 11],
            ['Losectil', 'Omeprazole', '20 mg', 'Capsule', 20, 'capsules', 18, 'Bogura', 1, 15],
        ];

        foreach ($rows as [$brand, $generic, $strength, $form, $qty, $unit, $months, $districtName, $donorIndex, $daysAgo]) {
            $medicine = Medicine::where(['generic_name' => $generic, 'strength' => $strength, 'dosage_form' => $form])->first();
            $district = District::where('name', $districtName)->first();
            if (! $medicine || ! $district) {
                continue;
            }

            $listing = new MedicineListing([
                'medicine_id' => $medicine->id,
                'medicine_alias_id' => $medicine->aliases()->where('brand_name', $brand)->value('id'),
                'brand_name' => $brand,
                'quantity' => $qty,
                'unit' => $unit,
                'expiry_date' => now()->addMonths($months)->startOfMonth(),
                'packaging' => 'sealed_box',
                'type' => 'donation',
                'division_id' => $district->division_id,
                'district_id' => $district->id,
                'upazila_id' => $district->upazilas()->orderBy('name')->value('id'),
            ]);
            $listing->user_id = $donors[$donorIndex]->id;
            $listing->status = ListingStatus::Available;
            $listing->submitted_at = now()->subDays($daysAgo + 1);
            $listing->published_at = now()->subDays($daysAgo);
            $listing->created_at = now()->subDays($daysAgo + 1);
            $listing->save();
        }

        // One open request for the demo user, so the dashboard shows matches.
        $demoUser = User::where('email', 'user@medimatch.test')->first();
        $paracetamol = Medicine::where(['generic_name' => 'Paracetamol', 'strength' => '500 mg', 'dosage_form' => 'Tablet'])->first();
        if ($demoUser && $paracetamol && ! $demoUser->medicineRequests()->exists()) {
            $request = new MedicineRequest([
                'medicine_id' => $paracetamol->id,
                'quantity' => 10,
                'unit' => 'tablets',
                'preference' => 'either',
                'division_id' => $demoUser->division_id,
                'district_id' => $demoUser->district_id,
                'upazila_id' => $demoUser->upazila_id,
            ]);
            $request->user_id = $demoUser->id;
            $request->expires_at = now()->addDays(config('medimatch.request_expiry_days'));
            $request->save();
        }
    }
}
