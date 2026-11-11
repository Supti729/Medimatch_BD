<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ComponentTest extends TestCase
{
    public function test_button_renders_a_button_by_default_and_a_link_when_given_an_href(): void
    {
        $this->blade('<x-button>Save</x-button>')
            ->assertSee('<button type="button"', false)
            ->assertSee('Save');

        $this->blade('<x-button href="/browse">Browse</x-button>')
            ->assertSee('<a href="/browse"', false)
            ->assertDontSee('<button', false);
    }

    public function test_status_badge_uses_fixed_wording_for_known_statuses(): void
    {
        $this->blade('<x-status-badge status="pending_review" />')->assertSee('Pending review');
        $this->blade('<x-status-badge status="reupload_required" />')->assertSee('Re-upload required');
        $this->blade('<x-status-badge status="AVAILABLE" />')->assertSee('Available');
    }

    public function test_status_badge_falls_back_to_a_readable_label_for_unknown_statuses(): void
    {
        $this->blade('<x-status-badge status="something_new" />')->assertSee('Something new');
    }

    public function test_status_badge_escapes_its_value(): void
    {
        $this->blade('<x-status-badge :status="$status" />', ['status' => '<script>alert(1)</script>'])
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_nav_link_is_a_real_link_only_when_its_route_exists(): void
    {
        $this->blade('<x-nav.link route="page-that-does-not-exist">Missing</x-nav.link>')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('Soon')
            ->assertDontSee('<a ', false);

        Route::get('/demo-page', fn () => 'ok')->name('demo-page');
        Route::getRoutes()->refreshNameLookups();

        $this->blade('<x-nav.link route="demo-page">Demo</x-nav.link>')
            ->assertSee('href="'.url('/demo-page').'"', false)
            ->assertDontSee('Soon');
    }

    public function test_input_shows_its_label_hint_and_required_marker(): void
    {
        $this->blade('<x-form.input name="quantity" label="Quantity" hint="Number of tablets" required />')
            ->assertSee('<label for="quantity"', false)
            ->assertSee('Number of tablets')
            ->assertSee('required', false)
            ->assertDontSee('aria-invalid', false);
    }

    public function test_input_shows_the_validation_error_instead_of_the_hint(): void
    {

        $this->withViewErrors(['quantity' => 'The quantity field is required.'])
            ->blade('<x-form.input name="quantity" label="Quantity" hint="Number of tablets" />')
            ->assertSee('The quantity field is required.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="quantity-error"', false)
            ->assertDontSee('Number of tablets');
    }

    public function test_password_input_never_outputs_a_value(): void
    {
        $this->blade('<x-form.input name="password" type="password" label="Password" value="secret123" />')
            ->assertDontSee('secret123');
    }

    public function test_select_marks_the_selected_option(): void
    {
        $this->blade('<x-form.select name="division" label="Division" :options="$options" selected="khulna" />', [
            'options' => ['dhaka' => 'Dhaka', 'khulna' => 'Khulna'],
        ])->assertSeeInOrder(['value="khulna"', 'selected'], false);
    }

    public function test_alert_uses_an_assertive_role_only_for_errors(): void
    {
        $this->blade('<x-alert tone="danger">Failed</x-alert>')->assertSee('role="alert"', false);
        $this->blade('<x-alert tone="success">Saved</x-alert>')->assertSee('role="status"', false);
    }

    public function test_flash_shows_session_messages(): void
    {
        session()->flash('success', 'Profile updated.');
        session()->flash('error', 'Upload failed.');

        $this->blade('<x-flash />')
            ->assertSee('Profile updated.')
            ->assertSee('Upload failed.');
    }

    public function test_confirm_modal_contains_a_csrf_protected_form_with_the_right_method(): void
    {
        $this->blade('<x-confirm-modal id="cancel" title="Cancel?" action="/listings/1" method="PATCH" confirm="Yes">Sure?</x-confirm-modal>')
            ->assertSee('<dialog id="cancel"', false)
            ->assertSee('action="/listings/1"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PATCH"', false);
    }

    public function test_stat_card_shows_a_dash_when_no_value_is_available(): void
    {
        $this->blade('<x-stat-card label="Completed" />')->assertSee('—');
        $this->blade('<x-stat-card label="Completed" :value="0" />')->assertSee('0')->assertDontSee('—');
    }
}
