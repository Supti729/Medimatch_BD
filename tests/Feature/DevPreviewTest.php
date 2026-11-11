<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The style guide and layout previews are developer tools.
 */
class DevPreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must not depend on compiled CSS/JS being present.
        $this->withoutVite();
    }

    /**
     * Tests run in the "testing" environment, so the preview routes
     * must not exist here (the same is true in production).
     */
    #[DataProvider('previewUrls')]
    public function test_preview_routes_are_not_available_outside_local_development(string $url): void
    {
        $this->get($url)->assertNotFound();
    }

    public static function previewUrls(): array
    {
        return [
            ['/styleguide'],
            ['/styleguide/components'],
            ['/styleguide/public'],
            ['/styleguide/auth'],
            ['/styleguide/app'],
            ['/styleguide/admin'],
        ];
    }

    public function test_style_guide_view_renders_the_design_tokens(): void
    {
        $this->view('dev.styleguide')
            ->assertSee('Medimatch BD style guide')
            ->assertSee('bg-brand-600')
            ->assertSee('Hind Siliguri');
    }

    public function test_component_gallery_renders_every_component_without_errors(): void
    {
        // The gallery links back to the style guide, so give that route a name for this test.
        Route::view('/styleguide', 'dev.styleguide')->name('styleguide');
        Route::getRoutes()->refreshNameLookups();

        $this->view('dev.components')
            ->assertSee('Buttons')
            ->assertSee('Pending review')
            ->assertSee('Re-upload required')
            ->assertSee('No listings yet');
    }
}
