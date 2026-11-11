<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_layout_has_navigation_footer_and_prototype_notice(): void
    {
        $this->blade('<x-layouts.public title="About">Page body</x-layouts.public>')
            ->assertSee('<title>About · Medimatch BD</title>', false)
            ->assertSee('Skip to main content')
            ->assertSee('Browse medicines')
            ->assertSee('Privacy policy')
            ->assertSee('Academic prototype.')
            ->assertSee('Page body');
    }

    public function test_user_layout_shows_account_menu_and_mobile_bottom_bar(): void
    {
        $this->blade('<x-layouts.app title="Dashboard">Page body</x-layouts.app>')
            ->assertSee('My listings')
            ->assertSee('Notifications')
            ->assertSee('aria-label="Quick"', false)
            ->assertSee('noindex')
            ->assertSee('Page body');
    }

    public function test_user_layout_shows_unread_notification_count_when_given(): void
    {
        $this->blade('<x-layouts.app title="Dashboard" :unread="3">Body</x-layouts.app>')
            ->assertSee('Notifications, 3 unread');

        $this->blade('<x-layouts.app title="Dashboard">Body</x-layouts.app>')
            ->assertDontSee('unread');
    }

    public function test_admin_layout_lists_admin_sections(): void
    {
        $this->blade('<x-layouts.admin title="Users">Page body</x-layouts.admin>')
            ->assertSee('<title>Users · Admin · Medimatch BD</title>', false)
            ->assertSee('Audit logs')
            ->assertSee('Verification')
            ->assertSee('Page body');
    }

    public function test_auth_layout_shows_heading_and_footer_slot(): void
    {
        $this->blade('<x-layouts.auth title="Log in" heading="Welcome back">Form<x-slot:footer>New here?</x-slot:footer></x-layouts.auth>')
            ->assertSee('Welcome back')
            ->assertSee('Form')
            ->assertSee('New here?');
    }

    public function test_logout_form_is_hidden_from_guests(): void
    {
        Route::post('/logout', fn () => null)->name('logout');
        Route::getRoutes()->refreshNameLookups();

        $this->blade('<x-layouts.app>Body</x-layouts.app>')->assertDontSee('Log out');
    }

    public function test_every_menu_item_in_the_navigation_config_has_a_label_and_route(): void
    {
        $flatten = function (array $menu) use (&$flatten): array {
            $items = [];
            foreach ($menu as $entry) {
                if (isset($entry['route'])) {
                    $items[] = $entry;
                } elseif (is_array($entry)) {
                    $items = array_merge($items, $flatten($entry));
                }
            }

            return $items;
        };

        $items = $flatten(config('navigation'));

        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertNotEmpty($item['label']);
            $this->assertNotEmpty($item['route']);
        }

        $this->assertCount(5, config('navigation.app_mobile'), 'The phone bottom bar should have exactly five items.');
    }
}
