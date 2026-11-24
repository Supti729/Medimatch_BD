<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    public function test_every_page_sends_the_security_headers(): void
    {
        $this->withoutVite();

        foreach (['/', '/login', '/medicines'] as $url) {
            $this->get($url)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
        }
    }

    public function test_prescription_documents_are_sandboxed_and_never_cached(): void
    {
        $this->seedMedicineData();
        Storage::fake('local');
        $requester = User::factory()->create();
        $request = $this->makeRequest($requester, $this->medicine('Amlodipine', '5 mg', 'Tablet'));
        $this->actingAs($requester)->post('/verification', ['medicine_request_id' => $request->id, 'document' => UploadedFile::fake()->image('p.jpg'), 'consent' => '1']);
        $submission = VerificationSubmission::first();

        $this->actingAs(User::factory()->reviewer()->create())->get("/admin/verifications/{$submission->id}/document")
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
    }

    public function test_private_storage_has_no_web_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }
}
