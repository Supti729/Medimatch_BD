<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\District;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(LocationSeeder::class);
        Notification::fake();
    }

    /** A valid form submission, with the Savar upazila of Dhaka district. */
    private function form(array $overrides = []): array
    {
        $district = District::where('name', 'Dhaka')->first();

        return array_merge([
            'name' => 'Nusrat Jahan',
            'email' => 'nusrat@example.com',
            'phone' => '',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'division_id' => $district->division_id,
            'district_id' => $district->id,
            'upazila_id' => $district->upazilas()->where('name', 'Savar')->value('id'),
            'terms' => '1',
        ], $overrides);
    }

    public function test_the_registration_page_lists_all_locations(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="division_id"', false)
            ->assertSee('name="upazila_id"', false)
            ->assertSee('Savar')
            ->assertDontSee('Not connected yet');
    }

    public function test_a_user_can_register_with_an_email_address(): void
    {
        $this->post('/register', $this->form())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'nusrat@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secret-pass-1', $user->password), 'The password must be stored hashed.');
        $this->assertNotSame('secret-pass-1', $user->password);
        $this->assertSame('Savar', $user->upazila->name);
        $this->assertFalse($user->isVerified());
        Notification::assertSentTo($user, VerificationCodeNotification::class);
    }

    public function test_a_user_can_register_with_only_a_phone_number_in_any_common_format(): void
    {
        $this->post('/register', $this->form(['email' => '', 'phone' => '+880 1712-345678']))->assertRedirect(route('verification.notice'));

        $user = User::where('phone', '01712345678')->first();
        $this->assertNotNull($user, 'The phone number should be stored in the normalised 01XXXXXXXXX form.');
        $this->assertNull($user->email);
        Notification::assertNothingSent(); // No SMS provider: nothing can be sent.
    }

    public function test_email_or_phone_is_required(): void
    {
        $this->post('/register', $this->form(['email' => '', 'phone' => '']))->assertSessionHasErrors(['email', 'phone']);
        $this->assertGuest();
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        User::factory()->create(['email' => 'nusrat@example.com']);

        $this->post('/register', $this->form(['email' => 'Nusrat@Example.com']))->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_duplicate_phone_is_rejected_even_in_another_format(): void
    {
        User::factory()->create(['phone' => '01712345678']);

        $this->post('/register', $this->form(['email' => '', 'phone' => '8801712345678']))->assertSessionHasErrors('phone');
        $this->assertSame(1, User::count());
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->post('/register', $this->form(['phone' => '12345']))->assertSessionHasErrors('phone');
        $this->post('/register', $this->form(['email' => 'not-an-email']))->assertSessionHasErrors('email');
        $this->post('/register', $this->form(['password_confirmation' => 'different']))->assertSessionHasErrors('password');
        $this->post('/register', $this->form(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post('/register', $this->form(['name' => '']))->assertSessionHasErrors('name');
        $this->post('/register', $this->form(['terms' => null]))->assertSessionHasErrors('terms');
        $this->assertSame(0, User::count());
    }

    public function test_the_location_must_be_consistent(): void
    {
        $sylhet = District::where('name', 'Sylhet')->first();

        // A district from a different division
        $this->post('/register', $this->form(['district_id' => $sylhet->id]))->assertSessionHasErrors('district_id');

        // An upazila from a different district
        $this->post('/register', $this->form(['upazila_id' => $sylhet->upazilas()->value('id')]))->assertSessionHasErrors('upazila_id');

        $this->assertSame(0, User::count());
    }

    public function test_users_cannot_give_themselves_a_role_or_status(): void
    {
        $this->post('/register', $this->form(['role' => 'admin', 'status' => 'suspended', 'email_verified_at' => now()]));

        $user = User::first();
        $this->assertSame(Role::User, $user->role);
        $this->assertSame(AccountStatus::Active, $user->status);
        $this->assertNull($user->email_verified_at);
    }

    public function test_an_optional_profile_picture_is_stored_and_other_files_are_rejected(): void
    {
        Storage::fake('public');

        $this->post('/register', $this->form(['avatar' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]))->assertSessionHasErrors('avatar');

        $this->post('/register', $this->form(['avatar' => UploadedFile::fake()->image('me.jpg', 200, 200)]));
        $user = User::first();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_signed_in_users_cannot_open_the_registration_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect(route('dashboard'));
    }
}
