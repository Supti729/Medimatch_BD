<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_the_login_page_is_connected(): void
    {
        $this->get('/login')->assertOk()->assertSee('action="'.route('login.store').'"', false)->assertDontSee('Not connected yet');
    }

    public function test_login_with_email(): void
    {
        $user = User::factory()->create(['email' => 'rahim@example.com', 'password' => 'secret-pass-1']);

        $this->post('/login', ['login' => 'Rahim@Example.com', 'password' => 'secret-pass-1'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_with_phone_number_in_any_common_format(): void
    {
        $user = User::factory()->phoneOnly()->create(['phone' => '01812345678', 'password' => 'secret-pass-1']);

        $this->post('/login', ['login' => '+880 1812-345678', 'password' => 'secret-pass-1'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_unknown_accounts_get_the_same_message(): void
    {
        User::factory()->create(['email' => 'rahim@example.com', 'password' => 'secret-pass-1']);

        $this->post('/login', ['login' => 'rahim@example.com', 'password' => 'wrong'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => 'nobody@example.com', 'password' => 'wrong'])->assertSessionHasErrors('login');

        $this->assertSame(session('errors')->first('login'), 'These details do not match an account. Check your email or phone number and password.');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        User::factory()->create(['email' => 'rahim@example.com', 'password' => 'secret-pass-1']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'rahim@example.com', 'password' => 'wrong']);
        }

        // Even the correct password is refused while locked out
        $this->post('/login', ['login' => 'rahim@example.com', 'password' => 'secret-pass-1'])->assertSessionHasErrors('login');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('login'));
        $this->assertGuest();
    }

    public function test_suspended_users_cannot_log_in(): void
    {
        User::factory()->suspended()->create(['email' => 'banned@example.com', 'password' => 'secret-pass-1']);

        $this->post('/login', ['login' => 'banned@example.com', 'password' => 'secret-pass-1'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_staff_are_sent_to_their_admin_area_after_login(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com', 'password' => 'secret-pass-1']);
        User::factory()->reviewer()->create(['email' => 'reviewer@example.com', 'password' => 'secret-pass-1']);

        $this->post('/login', ['login' => 'admin@example.com', 'password' => 'secret-pass-1'])->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');
        $this->post('/login', ['login' => 'reviewer@example.com', 'password' => 'secret-pass-1'])->assertRedirect(route('admin.verifications.index'));
    }

    public function test_login_returns_users_to_the_page_they_wanted(): void
    {
        User::factory()->create(['email' => 'rahim@example.com', 'password' => 'secret-pass-1']);

        $this->get('/listings')->assertRedirect(route('login'));
        $this->post('/login', ['login' => 'rahim@example.com', 'password' => 'secret-pass-1'])->assertRedirect('/listings');
    }

    public function test_remember_me_sets_a_remember_token(): void
    {
        $user = User::factory()->create(['email' => 'rahim@example.com', 'password' => 'secret-pass-1', 'remember_token' => null]);

        $this->post('/login', ['login' => 'rahim@example.com', 'password' => 'secret-pass-1', 'remember' => '1']);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_logout_requires_post_with_a_csrf_protected_form(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/logout')->assertStatus(405);
        $this->assertAuthenticated();
    }
}
