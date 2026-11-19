<?php

namespace Tests\Feature\Account;

use App\Enums\ListingStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\MatchFoundNotification;
use App\Notifications\ReservationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $donor;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        $this->donor = User::factory()->create(['name' => 'Rahim Uddin']);
        $this->requester = User::factory()->create(['name' => 'Nusrat Jahan']);
    }

    private function requestListing()
    {
        $listing = $this->makeListing($this->donor, brand: 'Napa');
        $this->makeRequest($this->requester);
        $this->actingAs($this->requester)->post("/medicines/{$listing->id}/reserve");

        return [$listing, Reservation::firstOrFail()];
    }

    public function test_a_new_request_notifies_the_donor(): void
    {
        $this->requestListing();

        $notification = $this->donor->notifications()->firstOrFail();
        $this->assertSame(ReservationNotification::class, $notification->type);
        $this->assertSame('Nusrat Jahan asked for your Napa 500 mg', $notification->data['title']);
        $this->assertSame(0, $this->requester->notifications()->count(), 'You are not notified about your own actions.');
    }

    public function test_accepting_declining_and_completing_notify_the_other_side(): void
    {
        [, $reservation] = $this->requestListing();

        $titles = fn (User $user) => $user->notifications()->get()->pluck('data.title')->all();

        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/accept");
        $this->assertContains('Rahim Uddin reserved Napa 500 mg for you', $titles($this->requester));

        $this->actingAs($this->requester)->patch("/reservations/{$reservation->id}/complete");
        $this->assertContains('Handover of Napa 500 mg completed', $titles($this->donor));
    }

    public function test_requesters_who_were_not_chosen_are_told(): void
    {
        [$listing, $chosen] = $this->requestListing();
        $other = User::factory()->create();
        $this->actingAs($other)->post("/medicines/{$listing->id}/reserve");

        $this->actingAs($this->donor)->patch("/reservations/{$chosen->id}/accept");

        $this->assertStringContainsString('was not accepted', $other->notifications()->first()->data['title']);
    }

    public function test_publishing_a_listing_tells_people_with_a_matching_request_nearby(): void
    {
        $nearby = User::factory()->create();
        $faraway = User::factory()->create();
        $this->makeRequest($nearby, district: 'Gazipur');   // Dhaka division
        $this->makeRequest($faraway, district: 'Sylhet');    // other division
        $listing = $this->makeListing($this->donor, ListingStatus::PendingReview, district: 'Dhaka');
        $listing->published_at = null;
        $listing->save();

        $listing->transitionTo(ListingStatus::Available); // what moderation will do in B5

        $this->assertSame(1, $nearby->notifications()->where('type', MatchFoundNotification::class)->count());
        $this->assertSame(0, $faraway->notifications()->count());
        $this->assertSame(0, $this->donor->notifications()->count());

        // Reactivating later does not notify again
        $listing->transitionTo(ListingStatus::Paused);
        $listing->transitionTo(ListingStatus::Available);
        $this->assertSame(1, $nearby->notifications()->count());
    }

    public function test_the_bell_shows_the_unread_count_and_latest_notifications(): void
    {
        $this->requestListing();

        $this->actingAs($this->donor)->get('/dashboard')
            ->assertSee('Notifications, 1 unread')
            ->assertSee('Nusrat Jahan asked for your Napa 500 mg');
    }

    public function test_opening_a_notification_marks_it_read_and_follows_its_link(): void
    {
        [$listing] = $this->requestListing();
        $notification = $this->donor->notifications()->first();

        $this->actingAs($this->donor)->get("/notifications/{$notification->id}/open")->assertRedirect(route('listings.edit', $listing, false));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->get('/dashboard')->assertDontSee('unread');
    }

    public function test_mark_all_as_read_and_filters(): void
    {
        [, $reservation] = $this->requestListing();
        $this->actingAs($this->requester)->post("/messages/{$reservation->conversation->id}", ['body' => 'Hello']);

        $this->actingAs($this->donor);
        $this->assertSame(2, $this->donor->unreadNotifications()->count());
        $this->get('/notifications?filter=unread')->assertSee('New message from Nusrat Jahan');

        $this->post('/notifications/read-all')->assertSessionHas('success');
        $this->assertSame(0, $this->donor->unreadNotifications()->count());
        $this->get('/notifications?filter=unread')->assertSee('No unread notifications');
    }

    public function test_users_cannot_open_someone_elses_notification(): void
    {
        $this->requestListing();
        $notification = $this->donor->notifications()->first();

        $this->actingAs(User::factory()->create())->get("/notifications/{$notification->id}/open")->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_a_notification_link_cannot_send_users_to_another_site(): void
    {
        $this->requestListing();
        $notification = $this->donor->notifications()->first();
        $notification->forceFill(['data' => ['type' => 'request', 'title' => 'x', 'text' => 'y', 'url' => '//evil.example.com']])->save();

        $this->actingAs($this->donor)->get("/notifications/{$notification->id}/open")->assertRedirect(route('notifications.index'));
    }
}
