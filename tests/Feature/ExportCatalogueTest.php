<?php

namespace Tests\Feature;

use Database\Seeders\MedicineCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_is_exported_for_the_ai_service_without_personal_data(): void
    {
        $this->seed(MedicineCatalogueSeeder::class);
        $path = sys_get_temp_dir().'/medimatch-catalogue-'.uniqid().'.json';

        $this->artisan('ai:export-catalogue', ['--path' => $path])->assertSuccessful();

        $data = json_decode(file_get_contents($path), true);
        unlink($path);

        $paracetamol = collect($data['medicines'])->firstWhere(fn ($m) => $m['generic_name'] === 'Paracetamol' && $m['strength'] === '500 mg');
        $this->assertEqualsCanonicalizing(['Napa', 'Ace'], $paracetamol['brands']);
        $this->assertFalse(collect($data['medicines'])->firstWhere('generic_name', 'Diazepam')['listable']);
        $this->assertSame(['id', 'generic_name', 'strength', 'dosage_form', 'requires_prescription', 'listable', 'brands'], array_keys($paracetamol));
    }
}
