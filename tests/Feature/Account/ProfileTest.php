<?php

namespace Tests\Feature\Account;

use App\Models\District;
use App\Models\User;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(LocationSeeder::class);
        Storage::fake('public');
        Notification::fake();
    }

    private function area(string $district = 'Sylhet'): array
    {
        $d = District::where('name', $district)->first();

        return ['division_id' => $d->division_id, 'district_id' => $d->id, 'upazila_id' => $d->upazilas()->value('id')];
    }

    public function test_the_profile_shows_the_real_account(): void
    {
        $user = User::factory()->create(['name' => 'Farzana Akter'] + $this->area('Khulna'));

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Farzana Akter')
            ->assertSee($user->email)
            ->assertSee('Khulna')
            ->assertDontSee('Preview User');
    }

    public function test_a_user_can_update_their_name_area_and_picture(): void
    {
        $user = User::factory()->create($this->area('Dhaka'));

        $this->actingAs($user)->put('/profile', ['name' => '  Farzana   Akter ', 'avatar' => UploadedFile::fake()->image('me.png')] + $this->area('Sylhet'))
            ->assertRedirect(route('profile.show'));

        $user->refresh();
        $this->assertSame('Farzana Akter', $user->name);
        $this->assertSame('Sylhet', $user->district->name);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_replacing_or_removing_the_picture_deletes_the_old_file(): void
    {
        $user = User::factory()->create($this->area());
        $this->actingAs($user)->put('/profile', ['name' => $user->name, 'avatar' => UploadedFile::fake()->image('one.png')] + $this->area());
        $first = $user->fresh()->avatar_path;

        $this->put('/profile', ['name' => $user->name, 'avatar' => UploadedFile::fake()->image('two.png')] + $this->area());
        Storage::disk('public')->assertMissing($first);

        $second = $user->fresh()->avatar_path;
        $this->put('/profile', ['name' => $user->name, 'remove_avatar' => '1'] + $this->area());
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_profile_input_is_validated(): void
    {
        $user = User::factory()->create($this->area());
        $this->actingAs($user);

        $this->put('/profile', ['name' => ''] + $this->area())->assertSessionHasErrors('name');
        $this->put('/profile', ['name' => 'Ok Name', 'avatar' => UploadedFile::fake()->create('virus.exe', 10)] + $this->area())->assertSessionHasErrors('avatar');
        $mismatch = $this->area('Dhaka');
        $mismatch['upazila_id'] = $this->area('Sylhet')['upazila_id'];
        $this->put('/profile', ['name' => 'Ok Name'] + $mismatch)->assertSessionHasErrors('upazila_id');
    }

    public function test_role_and_status_cannot_be_changed_through_the_profile(): void
    {
        $user = User::factory()->create($this->area());

        $this->actingAs($user)->put('/profile', ['name' => 'Name', 'role' => 'admin', 'status' => 'suspended'] + $this->area());

        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertFalse($user->fresh()->isSuspended());
    }

    public function test_privacy_settings_are_saved_and_unticked_boxes_mean_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/privacy', ['show_avatar' => '1'])->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->show_avatar);
        $this->assertFalse($user->show_contributions);
        $this->assertFalse($user->show_verified_badge);
        $this->assertFalse($user->matched_messages_only);
    }

    public function test_public_profile_shows_only_what_the_user_allows(): void
    {
        $user = User::factory()->create(['name' => 'Tanvir Hasan', 'phone' => '01711112222'] + $this->area());

        $this->get("/people/{$user->id}")
            ->assertOk()
            ->assertSee('Tanvir Hasan')
            ->assertSee('Verified')
            ->assertSee('completed donations')
            ->assertDontSee($user->email)
            ->assertDontSee('01711112222')
            ->assertDontSee('Sylhet');

        $user->update(['show_verified_badge' => false, 'show_contributions' => false]);
        $this->get("/people/{$user->id}")->assertDontSee('Verified')->assertDontSee('completed donation');
    }

    public function test_public_profile_hides_the_picture_when_the_user_says_so(): void
    {
        $user = User::factory()->create(['avatar_path' => 'avatars/me.png', 'show_avatar' => false]);

        $this->get("/people/{$user->id}")->assertDontSee('avatars/me.png');
        $this->actingAs($user)->get('/profile/edit')->assertSee('avatars/me.png'); // the owner still sees it
    }

    public function test_suspended_users_have_no_public_profile(): void
    {
        $user = User::factory()->suspended()->create();

        $this->get("/people/{$user->id}")->assertNotFound();
    }

    public function test_changing_the_email_requires_the_password_and_re_verification(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'phone' => null, 'phone_verified_at' => null, 'password' => 'secret-pass-1']);
        $this->actingAs($user);

        $this->put('/settings/contact', ['email' => 'new@example.com', 'phone' => '', 'confirm_password' => 'wrong'])->assertSessionHasErrors('confirm_password');
        $this->assertSame('old@example.com', $user->fresh()->email);

        $this->put('/settings/contact', ['email' => 'New@Example.com', 'phone' => '', 'confirm_password' => 'secret-pass-1'])
            ->assertRedirect(route('verification.notice'));

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_adding_a_phone_number_keeps_a_verified_email_account_usable(): void
    {
        $user = User::factory()->create(['phone' => null, 'password' => 'secret-pass-1']);

        $this->actingAs($user)->put('/settings/contact', ['email' => $user->email, 'phone' => '+880 1512-345678', 'confirm_password' => 'secret-pass-1'])
            ->assertSessionHas('success');

        $this->assertSame('01512345678', $user->fresh()->phone);
        $this->get('/dashboard')->assertOk();
    }

    public function test_contact_details_must_stay_unique_and_not_empty(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'phone' => '01999999999']);
        $user = User::factory()->create(['password' => 'secret-pass-1']);
        $this->actingAs($user);

        $this->put('/settings/contact', ['email' => 'taken@example.com', 'phone' => '', 'confirm_password' => 'secret-pass-1'])->assertSessionHasErrors('email');
        $this->put('/settings/contact', ['email' => '', 'phone' => '01999999999', 'confirm_password' => 'secret-pass-1'])->assertSessionHasErrors('phone');
        $this->put('/settings/contact', ['email' => '', 'phone' => '', 'confirm_password' => 'secret-pass-1'])->assertSessionHasErrors(['email', 'phone']);
    }
}
