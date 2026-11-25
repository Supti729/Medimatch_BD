<?php

namespace Tests\Feature;

use App\Models\MedicineListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class ImagePrivacyTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMedicineData();
        Storage::fake('public');
    }

    /** A real JPEG with an EXIF block containing GPS tags, like a phone photo. */
    private function photoWithGps(int $width = 2400, int $height = 1800): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();

        $exif = "Exif\0\0".'GPSLatitude 23.8103 GPSLongitude 90.4125 Home';
        $segment = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
        $withGps = substr($jpeg, 0, 2).$segment.substr($jpeg, 2); // right after the start-of-image marker

        $path = tempnam(sys_get_temp_dir(), 'gps').'.jpg';
        file_put_contents($path, $withGps);

        return new UploadedFile($path, 'IMG_2026.jpg', 'image/jpeg', null, true);
    }

    private function listingForm(array $extra = []): array
    {
        return array_merge([
            'medicine_id' => $this->medicine()->id, 'brand_name' => 'Napa', 'quantity' => 10, 'unit' => 'tablets',
            'expiry' => now()->addYear()->format('Y-m'), 'packaging' => 'sealed_box', 'type' => 'donation',
            'pickup_address' => 'House 12, Road 5, Bank Town, Savar',
            'confirm_sealed' => '1', 'confirm_expiry' => '1', 'confirm_storage' => '1', 'confirm_allowed' => '1', 'action' => 'draft',
        ], $this->area(), $extra);
    }

    public function test_listing_photos_lose_their_gps_metadata_and_are_scaled_down(): void
    {
        $upload = $this->photoWithGps();
        $this->assertStringContainsString('GPSLatitude', file_get_contents($upload->getRealPath()), 'The test photo should start with GPS data.');

        $this->actingAs(User::factory()->create())->post('/listings', $this->listingForm(['images' => [$upload]]))->assertSessionHasNoErrors();

        $stored = Storage::disk('public')->get(MedicineListing::first()->images->first()->path);
        $this->assertStringNotContainsString('GPSLatitude', $stored);
        $this->assertStringNotContainsString('Exif', $stored);

        [$width, $height] = getimagesizefromstring($stored);
        $this->assertSame([1600, 1200], [$width, $height]);
    }

    public function test_profile_pictures_lose_their_gps_metadata(): void
    {
        $user = User::factory()->create($this->area());

        $this->actingAs($user)->put('/profile', ['name' => $user->name, 'avatar' => $this->photoWithGps(800, 800)] + $this->area());

        $this->assertStringNotContainsString('GPSLatitude', Storage::disk('public')->get($user->fresh()->avatar_path));
    }

    public function test_images_too_large_to_process_safely_are_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/listings', $this->listingForm(['images' => [UploadedFile::fake()->image('huge.jpg', 9000, 100)]]))
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, MedicineListing::count());
    }
}
