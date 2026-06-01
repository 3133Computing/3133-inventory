<?php

namespace Tests\Feature\Branding;

use Tests\TestCase;

class Palette3133Test extends TestCase
{
    public function test_default_layout_uses_3133_colour_palette_for_light_and_dark_modes(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/default.blade.php'));

        $this->assertStringContainsString('--3133-navy: #0f172a;', $layout);
        $this->assertStringContainsString('--3133-blue: #3b82f6;', $layout);
        $this->assertStringContainsString('--3133-cyan: #06b6d4;', $layout);
        $this->assertStringContainsString('--main-theme-color: {{ $snipeSettings->header_color ?? \'var(--3133-blue)\' }};', $layout);
        $this->assertStringContainsString('--nav-primary-text-color: {{ $nav_link_color ?? \'#ffffff\' }};', $layout);

        $this->assertStringContainsString('[data-theme="light"]', $layout);
        $this->assertStringContainsString('--color-bg: #f8fafc;', $layout);
        $this->assertStringContainsString('--box-header-top-border-color: var(--3133-blue);', $layout);
        $this->assertStringContainsString('--link-color: {{ $link_light_color ?? \'#1d4ed8\' }};', $layout);
        $this->assertStringContainsString('linear-gradient(135deg, var(--3133-blue), var(--3133-cyan))', $layout);

        $this->assertStringContainsString('[data-theme="dark"]', $layout);
        $this->assertStringContainsString('--color-bg: #0f172a;', $layout);
        $this->assertStringContainsString('--box-bg: #1d293d;', $layout);
        $this->assertStringContainsString('--link-color: {{ $link_dark_color ?? \'#67e8f9\' }};', $layout);
        $this->assertStringContainsString('--sidenav-hover-color-bg: #1e3a8a;', $layout);
    }
}
