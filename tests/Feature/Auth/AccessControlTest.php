<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    #[DataProvider('accountPages')]
    public function test_guests_are_sent_to_login_from_account_pages(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    public static function accountPages(): array
    {
        return [['/dashboard'], ['/listings'], ['/requests'], ['/messages'], ['/profile'], ['/settings/security'], ['/verification']];
    }

    #[DataProvider('adminPages')]
    public function test_guests_are_sent_to_login_from_admin_pages(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    #[DataProvider('adminPages')]
    public function test_regular_users_cannot_open_admin_pages(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    public static function adminPages(): array
    {
        return [['/admin'], ['/admin/users'], ['/admin/listings'], ['/admin/medicines'], ['/admin/verifications'], ['/admin/reports'], ['/admin/transactions'], ['/admin/audit-logs'], ['/admin/settings']];
    }

    public function test_administrators_can_open_every_admin_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (self::adminPages() as [$url]) {
            $this->get($url)->assertOk();
        }
    }

    public function test_reviewers_can_open_only_the_verification_pages(): void
    {
        $this->actingAs(User::factory()->reviewer()->create());

        $this->get('/admin/verifications')->assertOk()->assertDontSee('Audit logs');
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
    }

    public function test_users_do_not_see_the_admin_panel_link_but_staff_do(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('Admin panel');
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertSee('Admin panel');
    }

    public function test_a_user_suspended_during_a_session_is_logged_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->status = AccountStatus::Suspended;
        $user->save();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_signed_in_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(route('dashboard'));
    }

    public function test_public_pages_stay_public(): void
    {
        foreach (['/', '/medicines', '/about', '/safety'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_regular_users_cannot_open_record_pages_or_post_admin_decisions(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $this->actingAs($user);

        $this->get("/admin/users/{$target->id}")->assertForbidden();
        $this->post("/admin/users/{$target->id}/status", ['decision' => 'suspend', 'note' => 'Not allowed.'])->assertForbidden();
        $this->post("/admin/users/{$user->id}/role", ['role' => 'reviewer', 'note' => 'Promote myself.'])->assertForbidden();
        $this->put('/admin/settings', ['min_shelf_life_months' => 1])->assertForbidden();

        $this->assertFalse($target->fresh()->isSuspended());
        $this->assertFalse($user->fresh()->role->isStaff());
    }

    public function test_reviewers_cannot_post_admin_decisions(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $target = User::factory()->create();

        $this->actingAs($reviewer)->post("/admin/users/{$target->id}/status", ['decision' => 'suspend', 'note' => 'Not my job.'])->assertForbidden();
        $this->assertFalse($target->fresh()->isSuspended());
    }

    public function test_error_pages_use_the_site_design(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden()->assertSee('You do not have access to this page');
        $this->get('/medicines/99999')->assertNotFound()->assertSee('Page not found');
    }
}
