<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[DataProvider('pages')]
    public function test_public_page_loads_with_its_heading(string $url, string $heading): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($heading)
            ->assertSee('Academic prototype.');
    }

    public static function pages(): array
    {
        return [
            'home' => ['/', 'Find the medicine you need.'],
            'browse' => ['/medicines', 'Browse medicines'],
            'how it works' => ['/how-it-works', 'From a need to a handover'],
            'safety' => ['/safety', 'Sharing medicines carefully'],
            'about' => ['/about', 'Project team'],
            'contact' => ['/contact', 'Get in touch with the project team'],
            'privacy' => ['/privacy', 'Privacy policy'],
            'terms' => ['/terms', 'Terms and conditions'],
            'login' => ['/login', 'Welcome back'],
            'register' => ['/register', 'Create your account'],
            'forgot password' => ['/forgot-password', 'Reset your password'],
            'reset password' => ['/reset-password/sample-token', 'Choose a new password'],
            'verify' => ['/verify', 'Verify your account'],
        ];
    }

    public function test_home_page_has_search_form_and_calls_to_action(): void
    {
        $this->get('/')
            ->assertSee('action="'.route('medicines.index').'"', false)
            ->assertSee('Donate medicine')
            ->assertSee('Request medicine')
            ->assertSee('What the AI does not do');
    }

    public function test_home_page_shows_no_invented_statistics(): void
    {
        $this->get('/')
            ->assertSee('no figures are shown')
            ->assertSee('Sample data');
    }

    public function test_main_menu_links_are_live_for_pages_that_exist(): void
    {
        $response = $this->get('/');

        foreach (['medicines.index', 'how-it-works', 'safety', 'about', 'contact', 'privacy', 'terms', 'login', 'register'] as $name) {
            $response->assertSee('href="'.route($name).'"', false);
        }
    }

    public function test_forms_without_a_backend_say_so_and_cannot_be_submitted(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/contact', '/verify'] as $url) {
            $this->get($url)
                ->assertSee('Not connected yet')
                ->assertSee('disabled', false);
        }
    }

    public function test_register_form_has_all_required_fields_and_64_districts(): void
    {
        $response = $this->get('/register');

        foreach (['name="name"', 'name="email"', 'name="phone"', 'name="password"', 'name="password_confirmation"', 'name="division"', 'name="district"', 'name="upazila"', 'name="avatar"', 'name="terms"'] as $field) {
            $response->assertSee($field, false);
        }

        $this->assertCount(8, config('bangladesh.divisions'));
        $this->assertSame(64, collect(config('bangladesh.divisions'))->flatten()->unique()->count());
    }

    public function test_reset_password_form_carries_the_token(): void
    {
        $this->get('/reset-password/abc123')->assertSee('name="token" value="abc123"', false);
    }
}
