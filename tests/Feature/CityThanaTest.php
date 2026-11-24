<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Upazila;
use App\Models\User;
use Database\Seeders\CityThanaSeeder;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CityThanaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(LocationSeeder::class);
    }

    private function district(string $name): District
    {
        return District::where('name', $name)->firstOrFail();
    }

    public function test_city_thanas_are_added_to_dhaka_and_chattogram(): void
    {
        $this->assertSame(50, $this->district('Dhaka')->upazilas()->where('code', 'like', 'DMP%')->count());
        $this->assertSame(15, $this->district('Chattogram')->upazilas()->where('code', 'like', 'CMP%')->count());
        $this->assertSame(495 + 65, Upazila::count());

        // The rural upazilas are still there, and Karnaphuli is not added twice.
        $this->assertTrue($this->district('Dhaka')->upazilas()->where('name', 'Savar')->exists());
        $this->assertSame(1, $this->district('Chattogram')->upazilas()->where('name', 'Karnaphuli')->count());
        $this->assertSame('ধানমন্ডি', $this->district('Dhaka')->upazilas()->where('name', 'Dhanmondi')->value('bn_name'));
    }

    public function test_seeding_again_does_not_duplicate_thanas(): void
    {
        $this->seed([LocationSeeder::class, CityThanaSeeder::class]);

        $this->assertSame(495 + 65, Upazila::count());
    }

    public function test_the_migration_adds_thanas_to_an_existing_database(): void
    {
        Upazila::where('code', 'like', 'DMP%')->orWhere('code', 'like', 'CMP%')->delete();
        $this->assertSame(495, Upazila::count());

        $migration = require database_path('migrations/2026_11_25_000001_add_city_thanas_to_upazilas.php');
        $migration->up();

        $this->assertSame(495 + 65, Upazila::count());
    }

    public function test_someone_can_register_in_a_city_thana(): void
    {
        Notification::fake();
        $dhaka = $this->district('Dhaka');

        $this->get('/register')->assertOk()->assertSee('Dhanmondi')->assertSee('Upazila / Thana');

        $this->post('/register', [
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.com',
            'phone' => '',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'division_id' => $dhaka->division_id,
            'district_id' => $dhaka->id,
            'upazila_id' => $dhaka->upazilas()->where('name', 'Dhanmondi')->value('id'),
            'terms' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Dhanmondi, Dhaka', User::where('email', 'nusrat@example.com')->firstOrFail()->load(['district', 'upazila'])->areaLabel());
    }
}
