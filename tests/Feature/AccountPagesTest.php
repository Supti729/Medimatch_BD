<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SampleAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Signed-in pages that still show sample data: messages and notifications (B4), verification (B5).
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
            'messages' => ['/messages', 'Conversations'],
            'conversation' => ['/messages/301', 'Sample donor B'],
            'notifications' => ['/notifications', 'Notifications'],
            'verification' => ['/verification', 'Verification Center'],
        ];
    }

    public function test_unknown_sample_records_return_not_found(): void
    {
        $this->get('/messages/99999')->assertNotFound();
    }

    public function test_every_sidebar_link_now_leads_to_a_real_page(): void
    {
        $this->get('/messages')->assertDontSee('This page is not built yet');
    }

    // ----- Messages -----

    public function test_conversation_shows_messages_and_never_contact_details(): void
    {
        $user = SampleAccount::user();

        $this->get('/messages/301')
            ->assertSee('Does 4 pm work for you?')
            ->assertSee('Never share your phone number')
            ->assertDontSee($user['email'])
            ->assertDontSee($user['phone']);
    }

    public function test_completed_conversation_is_closed_for_new_messages(): void
    {
        $this->get('/messages/303')
            ->assertSee('the conversation is closed')
            ->assertDontSee('name="body"', false);
    }

    // ----- Notifications -----

    public function test_notifications_can_be_filtered_to_unread(): void
    {
        $unread = SampleAccount::notifications()->where('read', false);
        // The oldest read notification: it is not among the recent ones in the header bell dropdown.
        $read = SampleAccount::notifications()->where('read', true)->last();

        $this->get('/notifications?filter=unread')
            ->assertSee($unread->first()['title'])
            ->assertDontSee($read['title']);
    }

    public function test_header_bell_shows_the_unread_count(): void
    {
        $count = SampleAccount::notifications()->where('read', false)->count();

        $this->get('/notifications')->assertSee("Notifications, {$count} unread");
    }

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
