<?php

namespace Tests\Feature;

use App\Support\SampleAccount;
use App\Support\SampleListings;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Signed-in user pages (front-end part: design previews on a sample account).
 */
class AccountPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[DataProvider('pages')]
    public function test_account_page_loads_and_is_labelled_as_a_preview(string $url, string $heading): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($heading)
            ->assertSee('Design preview')
            ->assertSee('noindex', false);
    }

    public static function pages(): array
    {
        return [
            'dashboard' => ['/dashboard', 'Welcome, Preview User'],
            'profile' => ['/profile', 'Public profile'],
            'edit profile' => ['/profile/edit', 'Edit profile'],
            'my listings' => ['/listings', 'My listings'],
            'create listing' => ['/listings/create', 'Donate a medicine'],
            'edit listing' => ['/listings/101/edit', 'Napa'],
            'my requests' => ['/requests', 'My requests'],
            'create request' => ['/requests/create', 'Request a medicine'],
            'request details' => ['/requests/201', 'Potential matches'],
            'saved' => ['/saved', 'Saved medicines'],
            'messages' => ['/messages', 'Conversations'],
            'conversation' => ['/messages/301', 'Sample donor B'],
            'notifications' => ['/notifications', 'Notifications'],
            'history' => ['/history', 'Interaction history'],
            'verification' => ['/verification', 'Verification Center'],
            'privacy settings' => ['/settings/privacy', 'Privacy settings'],
            'security settings' => ['/settings/security', 'Change password'],
        ];
    }

    public function test_every_sidebar_link_now_leads_to_a_real_page(): void
    {
        $this->get('/dashboard')->assertDontSee('This page is not built yet');
    }

    public function test_unknown_records_return_not_found(): void
    {
        $this->get('/listings/99999/edit')->assertNotFound();
        $this->get('/requests/99999')->assertNotFound();
        $this->get('/messages/99999')->assertNotFound();
        $this->get('/requests/abc')->assertNotFound();
    }

    // ----- Dashboard -----

    public function test_dashboard_numbers_are_counted_from_the_records_not_hardcoded(): void
    {
        $listings = SampleAccount::listings();
        $stats = SampleAccount::stats();

        $this->assertSame($listings->whereIn('status', ['available', 'requested'])->count(), $stats['active_listings']);
        $this->assertSame($listings->where('status', 'reserved')->count(), $stats['reserved']);
        $this->assertSame(SampleAccount::history()->where('status', 'completed')->count(), $stats['completed']);
    }

    public function test_dashboard_has_quick_actions_matches_and_activity(): void
    {
        $this->get('/dashboard')
            ->assertSee('Find medicine')
            ->assertSee('Donate medicine')
            ->assertSee('Request medicine')
            ->assertSee('Recommended matches')
            ->assertSee('Recent activity')
            ->assertSee('They are not medical advice');
    }

    // ----- Listings -----

    public function test_listing_tabs_filter_by_status(): void
    {
        $this->get('/listings?tab=closed')->assertSee('Ace')->assertDontSee('Alatrol');
        $this->get('/listings?tab=review')->assertSee('Alatrol')->assertSee('Reviewer note');
        $this->get('/listings?tab=nonsense')->assertOk()->assertSee('Alatrol')->assertSee('Ace');
    }

    public function test_editable_listing_shows_a_prefilled_form(): void
    {
        $this->get('/listings/101/edit')
            ->assertSee('value="Napa"', false)
            ->assertSee('value="Paracetamol"', false)
            ->assertSee('Submit for review')
            ->assertDontSee('This listing cannot be edited');
    }

    public function test_reserved_and_completed_listings_are_locked(): void
    {
        foreach ([103, 105] as $id) {
            $this->get("/listings/{$id}/edit")
                ->assertSee('This listing cannot be edited')
                ->assertDontSee('Submit for review');
        }
    }

    public function test_listing_form_offers_donation_only_to_individuals_and_requires_safety_confirmations(): void
    {
        $this->get('/listings/create')
            ->assertSee('Partners only')
            ->assertSee('name="confirm_sealed"', false)
            ->assertSee('name="confirm_expiry"', false)
            ->assertSee('name="confirm_storage"', false)
            ->assertSee('name="confirm_allowed"', false);
    }

    // ----- Requests and matching -----

    public function test_matches_must_agree_on_medicine_strength_and_form(): void
    {
        $request = SampleAccount::findRequest(201); // Paracetamol 500 mg tablet
        $matches = SampleAccount::matchesFor($request);

        $this->assertNotEmpty($matches);

        foreach ($matches as $listing) {
            $this->assertSame('Paracetamol', $listing['generic_name']);
            $this->assertSame('500 mg', $listing['strength']);
            $this->assertSame('Tablet', $listing['dosage_form']);
            $this->assertSame('available', $listing['status']);
        }

        $names = $matches->pluck('name');
        $this->assertFalse($names->contains('Napa Syrup'), 'A different dosage form must not match.');
        $this->assertFalse($names->contains('Ace Plus'), 'A combination medicine must not match.');
        $this->assertFalse($names->contains('Napa Extend'), 'A blocked listing with a different strength must not match.');
    }

    public function test_matches_in_the_same_district_come_first(): void
    {
        $request = SampleAccount::findRequest(201);
        $first = SampleAccount::matchesFor($request)->first();

        $this->assertSame($request['district'], $first['district']);
    }

    public function test_matches_never_include_listings_hidden_from_the_public(): void
    {
        $publicIds = SampleListings::visible()->pluck('id');

        foreach (SampleAccount::requests() as $request) {
            foreach (SampleAccount::matchesFor($request) as $listing) {
                $this->assertTrue($publicIds->contains($listing['id']));
            }
        }
    }

    public function test_closed_request_shows_no_matches(): void
    {
        $this->get('/requests/205')
            ->assertSee('Matching has stopped')
            ->assertDontSee('Cancel request');
    }

    public function test_request_needing_a_prescription_points_to_the_verification_center(): void
    {
        $this->get('/requests/202')
            ->assertSee('A prescription check is needed')
            ->assertSee(route('verification.index'), false);
    }

    public function test_request_form_can_be_prefilled_from_a_search(): void
    {
        $this->get('/requests/create?q=Napa')->assertSee('value="Napa"', false);
        $this->get('/requests/create?q[]=x')->assertOk();
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

        $this->get('/dashboard')->assertSee("Notifications, {$count} unread");
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

    public function test_history_can_be_filtered_by_role(): void
    {
        $this->get('/history?role=received')
            ->assertSee('ORSaline-N')
            ->assertDontSee('Ace 500 mg');
    }

    // ----- Honesty about what works -----

    public function test_forms_without_a_backend_say_so(): void
    {
        foreach (['/listings/create', '/requests/create', '/profile/edit', '/settings/privacy', '/settings/security', '/verification'] as $url) {
            $this->get($url)->assertSee('Not connected yet');
        }
    }
}
