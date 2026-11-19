<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Signed-in pages that still show sample data: the Verification Center (B5).
 */
class AccountPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->create(['name' => 'Rahim Uddin']));
    }

    #[DataProvider('pages')]
    public function test_account_page_loads_and_is_labelled_as_sample_data(string $url, string $heading): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($heading)
            ->assertSee('Sample data')
            ->assertSee('noindex', false);
    }

    public static function pages(): array
    {
        return [
            'verification' => ['/verification', 'Verification Center'],
        ];
    }

    public function test_every_sidebar_link_now_leads_to_a_real_page(): void
    {
        $this->get('/verification')->assertDontSee('This page is not built yet');
    }

    // ----- Messages -----

    // ----- Notifications -----

    // ----- Profile and privacy -----

    public function test_public_profile_preview_lists_what_is_never_shared(): void
    {
        $this->get('/profile')
            ->assertSee('This is all that other users see.')
            ->assertSee('Never shown to others')
            ->assertSee('Prescriptions and documents');
    }

    public function test_verification_center_explains_document_handling_and_warns_about_real_documents(): void
    {
        $this->get('/verification')
            ->assertSee('Never shown to donors')
            ->assertSee('AI does not approve or reject prescriptions')
            ->assertSee('Do not upload a real prescription')
            ->assertSee('Re-upload required');
    }

    // ----- Honesty about what works -----

    public function test_forms_without_a_backend_say_so(): void
    {
        foreach (['/verification'] as $url) {
            $this->get($url)->assertSee('Not connected yet');
        }
    }
}
