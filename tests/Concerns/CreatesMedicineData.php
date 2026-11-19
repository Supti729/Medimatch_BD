<?php

namespace Tests\Concerns;

use App\Enums\ListingStatus;
use App\Models\District;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\User;
use Database\Seeders\LocationSeeder;
use Database\Seeders\MedicineCatalogueSeeder;

/**
 * Helpers for tests that need catalogue medicines, listings and requests.
 * Call $this->seedMedicineData() in setUp().
 */
trait CreatesMedicineData
{
    protected function seedMedicineData(): void
    {
        $this->seed([LocationSeeder::class, MedicineCatalogueSeeder::class]);
    }

    protected function medicine(string $generic = 'Paracetamol', string $strength = '500 mg', string $form = 'Tablet'): Medicine
    {
        return Medicine::where(['generic_name' => $generic, 'strength' => $strength, 'dosage_form' => $form])->firstOrFail();
    }

    protected function area(string $districtName = 'Dhaka'): array
    {
        $district = District::where('name', $districtName)->firstOrFail();

        return [
            'division_id' => $district->division_id,
            'district_id' => $district->id,
            'upazila_id' => $district->upazilas()->orderBy('name')->value('id'),
        ];
    }

    protected function makeListing(?User $owner = null, ListingStatus $status = ListingStatus::Available, ?Medicine $medicine = null, string $brand = 'Napa', string $district = 'Dhaka', array $extra = []): MedicineListing
    {
        $medicine ??= $this->medicine();

        $listing = new MedicineListing(array_merge([
            'medicine_id' => $medicine->id,
            'medicine_alias_id' => $medicine->aliases()->where('brand_name', $brand)->value('id'),
            'brand_name' => $brand,
            'quantity' => 20,
            'unit' => 'tablets',
            'expiry_date' => now()->addYear()->startOfMonth(),
            'packaging' => 'sealed_box',
            'type' => 'donation',
        ], $this->area($district), $extra));
        $listing->user_id = ($owner ?? User::factory()->create())->id;
        $listing->status = $status;
        $listing->published_at = in_array($status, ListingStatus::publicStatuses(), true) ? now() : null;
        $listing->save();

        return $listing;
    }

    protected function makeRequest(User $owner, ?Medicine $medicine = null, string $district = 'Dhaka', array $extra = []): MedicineRequest
    {
        $request = new MedicineRequest(array_merge([
            'medicine_id' => ($medicine ?? $this->medicine())->id,
            'quantity' => 10,
            'unit' => 'tablets',
            'preference' => 'either',
        ], $this->area($district), $extra));
        $request->user_id = $owner->id;
        $request->expires_at = now()->addDays(60);
        $request->save();

        return $request;
    }
}
