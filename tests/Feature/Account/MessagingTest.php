<?php

namespace Tests\Feature\Account;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Support\ContactDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $donor;

    private User $requester;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();

        $this->donor = User::factory()->create(['name' => 'Rahim Uddin']);
        $this->requester = User::factory()->create(['name' => 'Nusrat Jahan']);

        $listing = $this->makeListing($this->donor);
        $this->actingAs($this->requester)->post("/medicines/{$listing->id}/reserve", ['message' => 'Hello, is it still available?']);
        $this->conversation = Conversation::firstOrFail();
    }

    public function test_asking_for_a_listing_opens_a_conversation_with_the_note_as_first_message(): void
    {
        $this->assertSame($this->donor->id, $this->conversation->donor_id);
        $this->assertSame($this->requester->id, $this->conversation->requester_id);
        $this->assertSame('Hello, is it still available?', $this->conversation->messages->first()->body);
    }

    public function test_participants_can_read_and_reply(): void
    {
        $this->actingAs($this->donor)->get("/messages/{$this->conversation->id}")->assertOk()->assertSee('Hello, is it still available?')->assertSee('Nusrat Jahan');

        $this->post("/messages/{$this->conversation->id}", ['body' => 'Yes. Can you meet outside the pharmacy on Friday?'])
            ->assertRedirect(route('messages.show', $this->conversation).'#latest');

        $this->actingAs($this->requester)->get("/messages/{$this->conversation->id}")->assertSee('Can you meet outside the pharmacy on Friday?');
    }

    public function test_nobody_else_can_read_write_or_poll_a_conversation(): void
    {
        $stranger = User::factory()->create();
        $this->actingAs($stranger);

        $this->get("/messages/{$this->conversation->id}")->assertForbidden();
        $this->post("/messages/{$this->conversation->id}", ['body' => 'Hi'])->assertForbidden();
        $this->getJson("/messages/{$this->conversation->id}/poll")->assertForbidden();
        $this->get('/messages')->assertDontSee('Hello, is it still available?');
        $this->assertSame(1, Message::count());
    }

    public function test_messages_are_validated(): void
    {
        $this->actingAs($this->donor);

        $this->post("/messages/{$this->conversation->id}", ['body' => '   '])->assertSessionHasErrors('body');
        $this->post("/messages/{$this->conversation->id}", ['body' => str_repeat('a', 1001)])->assertSessionHasErrors('body');
        $this->assertSame(1, Message::count());
    }

    public function test_phone_numbers_and_email_addresses_cannot_be_shared(): void
    {
        $this->actingAs($this->donor);

        foreach (['Call me on 01712345678', 'my number is +880 1712-345 678', 'email me: rahim@example.com', '017 1234 5678'] as $text) {
            $this->post("/messages/{$this->conversation->id}", ['body' => $text])->assertSessionHasErrors('body');
        }

        $this->assertSame(1, Message::count());
        $this->assertFalse(ContactDetails::found('I have 20 tablets of 500 mg, expiry 2027-08, batch 12345'));
    }

    public function test_the_conversation_closes_when_the_handover_ends(): void
    {
        $reservation = Reservation::first();
        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/decline");

        $this->get("/messages/{$this->conversation->id}")->assertOk()->assertSee('This conversation is closed');
        $this->post("/messages/{$this->conversation->id}", ['body' => 'One more thing'])->assertSessionHasErrors('body');
    }

    public function test_opening_a_conversation_marks_messages_as_read(): void
    {
        $this->actingAs($this->donor);
        $unread = fn () => $this->get('/messages')->viewData('conversations')->sum('unread_count');
        $this->assertSame(1, $unread());

        $this->get("/messages/{$this->conversation->id}");

        $this->assertNotNull($this->conversation->messages()->first()->read_at);
        $this->assertSame(0, $unread());
    }

    public function test_a_new_message_notifies_the_other_person_once_per_conversation(): void
    {
        $this->actingAs($this->donor);
        $this->post("/messages/{$this->conversation->id}", ['body' => 'First reply']);
        $this->post("/messages/{$this->conversation->id}", ['body' => 'Second reply']);

        $this->assertSame(1, $this->requester->unreadNotifications()->where('type', NewMessageNotification::class)->count());

        // Reading the conversation clears it
        $this->actingAs($this->requester)->get("/messages/{$this->conversation->id}");
        $this->assertSame(0, $this->requester->unreadNotifications()->where('type', NewMessageNotification::class)->count());
    }

    public function test_polling_returns_only_newer_messages_as_plain_text(): void
    {
        $first = $this->conversation->messages()->first();
        $this->actingAs($this->donor)->postJson("/messages/{$this->conversation->id}", ['body' => '<b>bold?</b> & more'])->assertCreated();

        $this->actingAs($this->requester)->getJson("/messages/{$this->conversation->id}/poll?after={$first->id}")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', '<b>bold?</b> & more')
            ->assertJsonPath('messages.0.mine', false)
            ->assertJsonPath('open', true);
    }

    public function test_message_text_is_escaped_in_the_page(): void
    {
        $this->actingAs($this->donor)->post("/messages/{$this->conversation->id}", ['body' => '<script>alert(1)</script>']);

        $this->actingAs($this->requester)->get("/messages/{$this->conversation->id}")->assertDontSee('<script>alert(1)</script>', false);
    }
}
