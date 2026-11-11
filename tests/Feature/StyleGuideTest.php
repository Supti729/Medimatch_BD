<?php

namespace Tests\Feature;

use Tests\TestCase;

class StyleGuideTest extends TestCase
{
    /**
     * The style guide is a developer tool. Tests run in the "testing"
     * environment, so the route must not exist here (same as production).
     */
    public function test_style_guide_is_not_available_outside_local_development(): void
    {
        $this->get('/styleguide')->assertNotFound();
    }

    public function test_style_guide_view_renders_the_design_tokens(): void
    {
        $this->view('dev.styleguide')
            ->assertSee('Medimatch BD style guide')
            ->assertSee('bg-brand-600')
            ->assertSee('Hind Siliguri');
    }
}
