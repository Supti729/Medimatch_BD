<?php

namespace Tests\Feature;

use App\Models\Medicine;
use Database\Seeders\MedicineCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(MedicineCatalogueSeeder::class);
        $count = Medicine::count();
        $this->seed(MedicineCatalogueSeeder::class);

        $this->assertSame($count, Medicine::count());
        $this->assertGreaterThan(15, $count);
    }

    public function test_one_identity_can_have_several_brand_names(): void
    {
        $this->seed(MedicineCatalogueSeeder::class);

        $paracetamol = Medicine::where(['generic_name' => 'Paracetamol', 'strength' => '500 mg', 'dosage_form' => 'Tablet'])->first();
        $this->assertSame(['Ace', 'Napa'], $paracetamol->aliases->pluck('brand_name')->all());
    }

    public function test_restricted_medicines_are_marked_with_a_reason(): void
    {
        $this->seed(MedicineCatalogueSeeder::class);

        $restricted = Medicine::where('eligibility', 'not_allowed')->get();
        $this->assertNotEmpty($restricted);
        $this->assertTrue($restricted->every(fn (Medicine $m) => filled($m->restriction_reason) && ! $m->isListable()));
    }
}
