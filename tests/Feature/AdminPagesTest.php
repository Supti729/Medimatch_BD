<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SampleAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Administration pages, signed in as an administrator. Records are sample data until B5.
 */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    #[DataProvider('pages')]
    public function test_admin_page_loads_and_is_labelled_as_sample_data(string $url, string $heading): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($heading)
            ->assertSee('Sample data')
            ->assertSee('noindex', false);
    }

    public static function pages(): array
    {
        return [
            'dashboard' => ['/admin', 'Overview'],
            'users' => ['/admin/users', 'User management'],
            'user details' => ['/admin/users/5', 'Sabbir Hossain'],
            'listings' => ['/admin/listings', 'Listing management'],
            'listing review' => ['/admin/listings/18', 'Moderation decision'],
            'requests' => ['/admin/requests', 'Request management'],
            'verification' => ['/admin/verifications', 'Verification center'],
            'document review' => ['/admin/verifications/601', 'Review decision'],
            'reports' => ['/admin/reports', 'Reports'],
            'report details' => ['/admin/reports/701', 'Resolve report'],
            'transactions' => ['/admin/transactions', 'Transaction monitoring'],
            'partners' => ['/admin/partners', 'Partner management'],
            'notifications' => ['/admin/notifications', 'Announcements'],
            'audit logs' => ['/admin/audit-logs', 'Audit log'],
            'settings' => ['/admin/settings', 'Admin settings'],
        ];
    }

    public function test_medicine_catalogue_page_shows_the_real_catalogue(): void
    {
        $this->seed(\Database\Seeders\MedicineCatalogueSeeder::class);

        $this->get('/admin/medicines')
            ->assertOk()
            ->assertSee('Napa')
            ->assertSee('Needs refrigeration (cold chain)')
            ->assertDontSee('Design preview');

        $this->get('/admin/medicines?q=seclo')->assertSee('Omeprazole')->assertDontSee('Fexo');
    }

    public function test_admin_login_page_loads(): void
    {
        auth()->logout();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Administrator log in')
            ->assertSee('cannot be requested or chosen during registration');
    }

    public function test_unknown_records_return_not_found(): void
    {
        foreach (['/admin/users/99999', '/admin/listings/99999', '/admin/verifications/99999', '/admin/reports/99999', '/admin/users/abc'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_every_menu_item_in_every_menu_points_to_an_existing_route(): void
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

    // ----- Dashboard -----

    public function test_dashboard_numbers_are_counted_from_the_records(): void
    {
        $stats = SampleAdmin::stats();

        $this->assertSame(SampleAdmin::users()->count(), $stats['total_users']);
        $this->assertSame(SampleAdmin::users()->where('status', 'active')->count(), $stats['active_users']);
        $this->assertSame(SampleAdmin::verifications()->whereIn('status', ['submitted', 'under_review'])->count(), $stats['pending_verification']);
        $this->assertSame(SampleAdmin::transactions()->where('status', 'completed')->count(), $stats['completed']);
    }

    public function test_chart_series_add_up_to_the_records_they_count(): void
    {
        $charts = SampleAdmin::charts();

        $this->assertCount(6, $charts['registrations'], 'Registrations should cover six months.');
        $this->assertSame(SampleAdmin::listings()->count(), array_sum($charts['listing_types']));
        $this->assertLessThanOrEqual(6, count($charts['listings_by_district']));

        $completedInRange = SampleAdmin::transactions()
            ->filter(fn ($t) => $t['completed_at'] && $t['completed_at']->gte(now()->startOfMonth()->subMonths(5)))
            ->count();
        $this->assertSame($completedInRange, array_sum($charts['completed']));
    }

    public function test_every_chart_has_a_table_view(): void
    {
        $response = $this->get('/admin');

        $this->assertGreaterThanOrEqual(4, substr_count($response->getContent(), 'View as table'));
    }

    // ----- Users and privacy -----

    public function test_user_search_and_filters(): void
    {
        $this->get('/admin/users?status=suspended')->assertSee('Sabbir Hossain')->assertDontSee('Nusrat Jahan');
        $this->get('/admin/users?q=sylhet')->assertSee('Tanvir Hasan')->assertDontSee('Rahim Uddin');
        $this->get('/admin/users?role=nonsense&status[]=x')->assertOk();
    }

    public function test_admin_user_page_masks_contact_details(): void
    {
        $user = SampleAdmin::findUser(1);

        $this->get('/admin/users/1')
            ->assertDontSee($user['email'])
            ->assertDontSee($user['phone'])
            ->assertSee('partly hidden');
    }

    public function test_staff_accounts_cannot_be_suspended_from_the_user_page(): void
    {
        $this->get('/admin/users/14')
            ->assertSee('cannot be suspended')
            ->assertDontSee('Suspend account');

        $this->get('/admin/users/5')->assertSee('Restore account');
        $this->get('/admin/users/1')->assertSee('Suspend account');
    }

    // ----- Moderation -----

    public function test_admin_sees_hidden_listings_that_the_public_cannot(): void
    {
        $this->get('/admin/listings?tab=blocked')->assertSee('Napa Extend');
        $this->get('/medicines/17')->assertNotFound();
    }

    public function test_listing_review_offers_actions_that_fit_the_status(): void
    {
        $this->get('/admin/listings/18')->assertSee('Approve')->assertSee('Reject'); // pending review
        $this->get('/admin/listings/17')->assertSee('Unblock')->assertDontSee('Approve'); // blocked
        $this->get('/admin/listings/1')->assertSee('Block')->assertDontSee('Approve'); // available
    }

    public function test_decisions_require_a_reason_and_are_not_connected_yet(): void
    {
        foreach (['/admin/listings/18', '/admin/verifications/601', '/admin/reports/701'] as $url) {
            $this->get($url)
                ->assertSee('name="note"', false)
                ->assertSee('Not connected yet');
        }
    }

    public function test_document_review_never_shows_a_real_document_and_warns_reviewers(): void
    {
        $this->get('/admin/verifications/601')
            ->assertSee('No document in the design preview')
            ->assertSee('Shown only to authorized reviewers');

        $this->get('/admin/verifications')->assertSee('never use AI to approve or reject a prescription');
    }

    public function test_decided_verifications_and_closed_reports_have_no_decision_form(): void
    {
        $this->get('/admin/verifications/605')->assertSee('has been decided')->assertDontSee('name="decision"', false);
        $this->get('/admin/reports/704')->assertDontSee('name="decision"', false);
    }

    public function test_audit_log_can_be_searched_and_filtered(): void
    {
        $this->get('/admin/audit-logs?area=verification')
            ->assertSee('verification.verified')
            ->assertDontSee('listing.blocked');

        $this->get('/admin/audit-logs?q=napa')->assertSee('listing.blocked')->assertDontSee('user.suspended');
    }

    public function test_partner_plans_are_clearly_marked_as_mock(): void
    {
        $this->get('/admin/partners')
            ->assertSee('Mock plans · no payments')
            ->assertSee('personal and prescription data is never sold');
    }

    public function test_settings_keep_affordable_access_off_and_explain_roles(): void
    {
        $this->assertFalse(SampleAdmin::settings()['allow_affordable_access']);

        $this->get('/admin/settings')->assertSee('Users can never choose their own role');
    }
}
