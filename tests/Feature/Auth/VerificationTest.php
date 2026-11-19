<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use App\Services\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    public function test_unverified_users_are_sent_to_the_verification_page(): void
    {
        $this->actingAs(User::factory()->unverified()->create());

        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->get('/verify')->assertOk()->assertSee('Verify your account');
    }

    public function test_the_correct_code_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        $code = null;
        app(VerificationCodeService::class)->send($user);
        Notification::assertSentTo($user, VerificationCodeNotification::class, function ($notification) use (&$code, $user) {
            preg_match('/\*\*(\d{6})\*\*/', implode(' ', $notification->toMail($user)->introLines), $m);
            $code = $m[1] ?? null;

            return true;
        });

        $this->actingAs($user)->post('/verify', ['code' => $code])->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/dashboard')->assertOk();
    }

    public function test_phone_only_accounts_verify_their_phone(): void
    {
        config(['medimatch.dev_codes_on_screen' => true]);
        $user = User::factory()->phoneOnly()->unverified()->create();

        $result = app(VerificationCodeService::class)->send($user);
        $this->assertSame('phone', $result['channel']);
        $this->assertFalse($result['delivered'], 'No SMS provider is configured, so the code must not count as delivered.');

        $this->actingAs($user)->post('/verify', ['code' => $result['dev_code']]);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_development_codes_are_shown_only_when_enabled(): void
    {
        $user = User::factory()->phoneOnly()->unverified()->create();

        config(['medimatch.dev_codes_on_screen' => false]);
        $this->assertNull(app(VerificationCodeService::class)->send($user)['dev_code']);

        config(['medimatch.dev_codes_on_screen' => true]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', app(VerificationCodeService::class)->send($user)['dev_code']);
    }

    public function test_wrong_codes_are_rejected_and_attempts_are_limited(): void
    {
        config(['medimatch.dev_codes_on_screen' => true]);
        $user = User::factory()->unverified()->create();
        $code = app(VerificationCodeService::class)->send($user)['dev_code'];
        $wrong = $code === '111111' ? '222222' : '111111';

        $this->actingAs($user);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/verify', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        // After 5 wrong tries, even the right code no longer works
        $this->post('/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->isVerified());
    }

    public function test_codes_expire(): void
    {
        config(['medimatch.dev_codes_on_screen' => true]);
        $user = User::factory()->unverified()->create();
        $code = app(VerificationCodeService::class)->send($user)['dev_code'];

        $this->travel(11)->minutes();

        $this->actingAs($user)->post('/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->isVerified());
    }

    public function test_a_new_code_can_be_requested(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->from('/verify')->post('/verify/resend')->assertRedirect('/verify');
        Notification::assertSentTo($user, VerificationCodeNotification::class);
    }
}
