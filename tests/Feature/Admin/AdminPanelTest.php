<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Medicine;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\MatchFoundNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Person']);
    }

    #[DataProvider('pages')]
    public function test_every_admin_page_loads_with_real_data(string $url, string $heading): void
    {
        $listing = $this->makeListing(brand: 'Napa');
        $this->makeRequest(User::factory()->create());

        $this->actingAs($this->admin)->get($url)->assertOk()->assertSee($heading)->assertDontSee('Sample data');
    }

    public static function pages(): array
    {
        return [
            'dashboard' => ['/admin', 'Overview'],
            'users' => ['/admin/users', 'User management'],
            'catalogue' => ['/admin/medicines', 'Medicine catalogue'],
            'listings' => ['/admin/listings', 'Listing management'],
            'requests' => ['/admin/requests', 'Request management'],
            'verifications' => ['/admin/verifications', 'Verification center'],
            'reports' => ['/admin/reports', 'Reports'],
            'transactions' => ['/admin/transactions', 'Transaction monitoring'],
            'partners' => ['/admin/partners', 'Partner management'],
            'announcements' => ['/admin/notifications', 'Announcements'],
            'audit log' => ['/admin/audit-logs', 'Audit log'],
            'settings' => ['/admin/settings', 'Admin settings'],
        ];
    }

    public function test_every_menu_item_points_to_an_existing_route(): void
    {
        $flatten = function (array $menu) use (&$flatten): array {
            $routes = [];
            foreach ($menu as $entry) {
                if (isset($entry['route'])) {
                    $routes[] = $entry['route'];
                } elseif (is_array($entry)) {
                    $routes = array_merge($routes, $flatten($entry));
                }
            }

            return $routes;
        };

        foreach ($flatten(config('navigation')) as $name) {
            $this->assertTrue(Route::has($name), "Menu route [{$name}] does not exist.");
        }
    }

    public function test_dashboard_numbers_are_counted_from_the_database(): void
    {
        $this->makeListing(status: ListingStatus::PendingReview);
        $this->makeListing(status: ListingStatus::Available);

        $stats = $this->actingAs($this->admin)->get('/admin')->viewData('stats');

        $this->assertSame(User::count(), $stats['total_users']);
        $this->assertSame(2, $stats['total_listings']);
        $this->assertSame(1, $stats['active_listings']);
    }

    // ----- Listing moderation -----

    public function test_approving_a_listing_publishes_it_notifies_the_owner_and_matching_requesters(): void
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $this->makeRequest($requester, district: 'Dhaka');
        $listing = $this->makeListing($owner, ListingStatus::PendingReview);
        $listing->published_at = null;
        $listing->save();

        $this->actingAs($this->admin)->post("/admin/listings/{$listing->id}/decision", ['decision' => 'approve', 'note' => 'Photos and expiry look fine.'])
            ->assertSessionHas('success');

        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
        $this->assertNotNull($listing->fresh()->published_at);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $requester->notifications()->where('type', MatchFoundNotification::class)->count());
        $this->assertTrue(AuditLog::where('action', 'listing.approved')->where('subject_id', $listing->id)->where('actor_id', $this->admin->id)->exists());
        $this->get("/medicines/{$listing->id}")->assertOk();
    }

    public function test_rejecting_and_blocking_need_a_reason_and_hide_the_listing(): void
    {
        $pending = $this->makeListing(status: ListingStatus::PendingReview);
        $live = $this->makeListing();
        $this->actingAs($this->admin);

        $this->post("/admin/listings/{$pending->id}/decision", ['decision' => 'reject', 'note' => ''])->assertSessionHasErrors('note');
        $this->post("/admin/listings/{$pending->id}/decision", ['decision' => 'reject', 'note' => 'Expiry date is unreadable.']);
        $this->assertSame(ListingStatus::Rejected, $pending->fresh()->status);
        $this->assertSame('Expiry date is unreadable.', $pending->fresh()->review_note);

        $this->post("/admin/listings/{$live->id}/decision", ['decision' => 'block', 'note' => 'Reported as opened pack.']);
        $this->assertSame(ListingStatus::Blocked, $live->fresh()->status);
        $this->get("/medicines/{$live->id}")->assertNotFound();

        $this->post("/admin/listings/{$live->id}/decision", ['decision' => 'unblock', 'note' => 'Checked again, it is fine.']);
        $this->assertSame(ListingStatus::Available, $live->fresh()->status);
    }

    public function test_decisions_that_do_not_fit_the_status_are_refused(): void
    {
        $live = $this->makeListing();

        $this->actingAs($this->admin)->post("/admin/listings/{$live->id}/decision", ['decision' => 'approve', 'note' => 'Approve again?'])->assertSessionHasErrors('decision');
        $this->assertSame(ListingStatus::Available, $live->fresh()->status);
    }

    public function test_blocking_releases_people_waiting_for_the_listing(): void
    {
        $listing = $this->makeListing();
        $this->actingAs(User::factory()->create())->post("/medicines/{$listing->id}/reserve");

        $this->actingAs($this->admin)->post("/admin/listings/{$listing->id}/decision", ['decision' => 'block', 'note' => 'Unsafe listing.']);

        $this->assertSame('declined', Reservation::first()->status->value);
    }

    // ----- Users -----

    public function test_suspending_a_user_hides_their_listings_and_logs_them_out(): void
    {
        $user = User::factory()->create();
        $listing = $this->makeListing($user);

        $this->actingAs($this->admin)->post("/admin/users/{$user->id}/status", ['decision' => 'suspend', 'note' => 'Repeated selling reports.'])->assertSessionHas('success');

        $this->assertTrue($user->fresh()->isSuspended());
        $this->get("/medicines/{$listing->id}")->assertNotFound();
        $this->assertTrue(AuditLog::where('action', 'user.suspended')->where('subject_id', $user->id)->exists());

        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route('login'));

        $this->actingAs($this->admin)->post("/admin/users/{$user->id}/status", ['decision' => 'restore', 'note' => 'Appeal accepted.']);
        $this->assertFalse($user->fresh()->isSuspended());
        $this->get("/medicines/{$listing->id}")->assertOk();
    }

    public function test_admins_cannot_change_staff_or_themselves(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $this->actingAs($this->admin);

        $this->post("/admin/users/{$reviewer->id}/status", ['decision' => 'suspend', 'note' => 'Testing staff.'])->assertSessionHasErrors('decision');
        $this->post("/admin/users/{$this->admin->id}/status", ['decision' => 'suspend', 'note' => 'Testing myself.'])->assertSessionHasErrors('decision');
        $this->assertFalse($reviewer->fresh()->isSuspended());
    }

    public function test_roles_can_be_changed_but_never_to_administrator(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin);

        $this->post("/admin/users/{$user->id}/role", ['role' => 'partner', 'note' => 'Licensed pharmacy, checked.']);
        $this->assertSame(Role::Partner, $user->fresh()->role);

        $this->post("/admin/users/{$user->id}/role", ['role' => 'admin', 'note' => 'Promote?'])->assertSessionHasErrors('role');
        $this->assertSame(Role::Partner, $user->fresh()->role);
    }

    public function test_admin_user_page_masks_contact_details(): void
    {
        $user = User::factory()->create(['email' => 'rahim.uddin@example.com', 'phone' => '01712345678']);

        $this->actingAs($this->admin)->get("/admin/users/{$user->id}")->assertDontSee('rahim.uddin@example.com')->assertDontSee('01712345678')->assertSee('partly hidden');
    }

    // ----- Catalogue, announcements, settings -----

    public function test_admins_can_add_and_edit_catalogue_medicines(): void
    {
        $this->actingAs($this->admin)->post('/admin/medicines', [
            'generic_name' => 'Cefuroxime', 'strength' => '500 mg', 'dosage_form' => 'Tablet',
            'requires_prescription' => '1', 'eligibility' => 'allowed', 'brand_names' => 'Kilbac, Cefotil',
        ])->assertSessionHas('success');

        $medicine = Medicine::where('generic_name', 'Cefuroxime')->first();
        $this->assertTrue($medicine->requires_prescription);
        $this->assertEqualsCanonicalizing(['Kilbac', 'Cefotil'], $medicine->aliases->pluck('brand_name')->all());

        // Duplicates are refused, and restricting needs a reason
        $this->post('/admin/medicines', ['generic_name' => 'Cefuroxime', 'strength' => '500 mg', 'dosage_form' => 'Tablet', 'eligibility' => 'allowed'])->assertSessionHasErrors('strength');
        $this->put("/admin/medicines/{$medicine->id}", ['generic_name' => 'Cefuroxime', 'strength' => '500 mg', 'dosage_form' => 'Tablet', 'eligibility' => 'not_allowed'])->assertSessionHasErrors('restriction_reason');

        $this->put("/admin/medicines/{$medicine->id}", ['generic_name' => 'Cefuroxime', 'strength' => '500 mg', 'dosage_form' => 'Tablet', 'eligibility' => 'allowed', 'brand_names' => 'Kilbac']);
        $this->assertSame(['Kilbac'], $medicine->fresh()->aliases->pluck('brand_name')->all());
    }

    public function test_announcements_reach_the_chosen_audience(): void
    {
        $donor = User::factory()->create();
        $this->makeListing($donor);
        $other = User::factory()->create();
        User::factory()->suspended()->create();

        $this->actingAs($this->admin)->post('/admin/notifications', ['title' => 'Safety reminder', 'message' => 'Meet in public places.', 'audience' => 'donors'])->assertSessionHas('success');

        $this->assertSame(1, $donor->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());
        $this->assertTrue(AuditLog::where('action', 'announcement.sent')->exists());
    }

    public function test_settings_change_the_listing_rules_and_are_audited(): void
    {
        $this->actingAs($this->admin)->put('/admin/settings', [
            'min_shelf_life_months' => 6, 'max_quantity' => 500, 'max_photos' => 3, 'request_expiry_days' => 30,
        ])->assertSessionHas('success');

        $this->assertSame('6', Setting::find('min_shelf_life_months')->value);
        Setting::applyToConfig();
        $this->assertSame(6, config('medimatch.listing_rules.min_shelf_life_months'));
        $this->assertFalse(config('medimatch.allow_affordable_access'));
        $this->assertTrue(AuditLog::where('action', 'settings.updated')->exists());

        $this->put('/admin/settings', ['min_shelf_life_months' => 0, 'max_quantity' => 500, 'max_photos' => 3, 'request_expiry_days' => 30])->assertSessionHasErrors('min_shelf_life_months');
    }

    public function test_the_audit_log_cannot_be_changed_or_deleted(): void
    {
        $log = AuditLog::record('test.action', null, 'Something happened');

        try {
            $log->delete();
            $this->fail('Deleting an audit entry should be refused.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->forceFill(['description' => 'Changed'])->save();
    }

    public function test_the_audit_log_can_be_searched(): void
    {
        AuditLog::record('listing.blocked', null, 'Blocked listing #3 Napa');
        AuditLog::record('user.suspended', null, 'Suspended user #9');

        $this->actingAs($this->admin)->get('/admin/audit-logs?area=listing')->assertSee('Blocked listing #3 Napa')->assertDontSee('Suspended user #9');
        $this->get('/admin/audit-logs?q=napa')->assertSee('Blocked listing #3 Napa');
    }
}
