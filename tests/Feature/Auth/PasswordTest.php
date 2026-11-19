<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    private function resetLinkFor(User $user): string
    {
        $url = null;
        Notification::assertSentTo($user, PasswordResetNotification::class, function ($notification) use (&$url, $user) {
            $url = $notification->toMail($user)->actionUrl;

            return true;
        });

        return $url;
    }

    public function test_a_reset_link_is_emailed_and_resets_the_password(): void
    {
        $user = User::factory()->create(['email' => 'rahim@example.com', 'password' => 'old-password-1']);

        $this->post('/forgot-password', ['login' => 'rahim@example.com'])->assertSessionHas('status');
        $url = $this->resetLinkFor($user);

        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->get($url)->assertOk()->assertSee('value="rahim@example.com"', false);

        $this->post('/reset-password', [
            'token' => $token,
            'login' => 'rahim@example.com',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));

        // A reset link works only once
        $this->post('/reset-password', ['token' => $token, 'login' => 'rahim@example.com', 'password' => 'another-pass-1', 'password_confirmation' => 'another-pass-1'])
            ->assertSessionHasErrors('login');
    }

    public function test_the_answer_does_not_reveal_whether_an_account_exists(): void
    {
        User::factory()->create(['email' => 'rahim@example.com']);

        $known = $this->post('/forgot-password', ['login' => 'rahim@example.com'])->getSession()->get('status');
        $unknown = $this->post('/forgot-password', ['login' => 'nobody@example.com'])->getSession()->get('status');

        $this->assertSame($known, $unknown);
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'rahim@example.com', 'password' => 'old-password-1']);

        $this->post('/reset-password', ['token' => 'made-up', 'login' => 'rahim@example.com', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertSessionHasErrors('login');
        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));
    }

    public function test_a_token_for_one_account_cannot_reset_another(): void
    {
        $victim = User::factory()->create(['email' => 'victim@example.com', 'password' => 'victim-pass-1']);
        $attacker = User::factory()->create(['email' => 'attacker@example.com']);

        $this->post('/forgot-password', ['login' => 'attacker@example.com']);
        $token = basename(parse_url($this->resetLinkFor($attacker), PHP_URL_PATH));

        $this->post('/reset-password', ['token' => $token, 'login' => 'victim@example.com', 'password' => 'hacked-pass-1', 'password_confirmation' => 'hacked-pass-1'])
            ->assertSessionHasErrors('login');
        $this->assertTrue(Hash::check('victim-pass-1', $victim->fresh()->password));
    }

    public function test_phone_only_accounts_get_an_on_screen_link_only_in_development_mode(): void
    {
        User::factory()->phoneOnly()->create(['phone' => '01912345678']);

        config(['medimatch.dev_codes_on_screen' => false]);
        $this->post('/forgot-password', ['login' => '01912345678'])->assertSessionMissing('dev_reset_link');

        $this->travel(2)->minutes(); // past the "recently requested" throttle
        config(['medimatch.dev_codes_on_screen' => true]);
        $this->post('/forgot-password', ['login' => '01912345678'])->assertSessionHas('dev_reset_link');

        Notification::assertNothingSent();
    }

    public function test_signed_in_users_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);

        $this->actingAs($user)->from('/settings/security')->put('/settings/password', [
            'current_password' => 'wrong',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('current_password');

        $this->from('/settings/security')->put('/settings/password', [
            'current_password' => 'old-password-1',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertRedirect('/settings/security')->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
    }
}
