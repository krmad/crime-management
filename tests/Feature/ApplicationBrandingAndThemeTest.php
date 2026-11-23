<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('app.brand_name', 'Crime Management Operations');
});

test('application configuration uses the full brand name', function () {
    $config = file_get_contents(config_path('app.php'));

    expect($config)
        ->toContain("'name' => env('APP_BRAND_NAME', 'Crime Management Operations')")
        ->toContain("'brand_name' => env('APP_BRAND_NAME', 'Crime Management Operations')");
});

test('the branded application name is used in browser titles and the visible lockup', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<title>Crime Management Operations</title>', false)
        ->assertSee('aria-label="Crime Management Operations"', false)
        ->assertSee('>Crime Management</text>', false)
        ->assertSee('>OPERATIONS</text>', false);

    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('<title>Crime Management Operations</title>', false)
        ->assertSee('aria-label="Crime Management Operations"', false)
        ->assertSee('>Crime Management</text>', false);
});

test('theme controls restore and persist the preference on public and authenticated pages', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-theme-toggle', false)
        ->assertSee("localStorage.getItem('theme')", false)
        ->assertSee("classList.toggle('dark'", false)
        ->assertSee("localStorage.setItem('theme'", false)
        ->assertSee('@click="toggle()"', false)
        ->assertSee('x-show="!dark"', false)
        ->assertSee('x-show="dark"', false);

    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('data-theme-toggle', false)
        ->assertSee("localStorage.getItem('theme')", false)
        ->assertSee("localStorage.setItem('theme'", false);
});

test('the logo is a reusable inline shield and checkmark lockup', function () {
    $logo = file_get_contents(resource_path('views/components/application-logo.blade.php'));

    expect($logo)
        ->toContain('<svg')
        ->toContain("'viewBox' => '0 0 220 44'")
        ->toContain('>Crime Management</text>')
        ->toContain('>OPERATIONS</text>')
        ->toContain('stroke-[#318FCB]')
        ->toContain('dark:stroke-[#60A5FA]')
        ->not->toContain('police');
});

test('dark mode changes the real palette while printing keeps a white background', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('@custom-variant dark (&:where(.dark, .dark *));')
        ->toContain('.dark {')
        ->toContain('color-scheme: dark;')
        ->toContain('--color-canvas: #F5F8FC;')
        ->toContain('--color-surface: #FFFFFF;')
        ->toContain('--color-canvas: #0F172A;')
        ->toContain('--color-surface: #1E293B;')
        ->toContain('--color-line: #334155;')
        ->toContain('--color-ink: #F8FAFC;')
        ->toContain('--color-muted: #94A3B8;')
        ->toContain('--color-brand: #60A5FA;')
        ->toContain('--tw-ring-offset-color: var(--color-surface);')
        ->toContain('@media print')
        ->toContain('color-scheme: light !important;')
        ->toContain('--color-success: #2F9E68;')
        ->toContain('background: #fff !important;');
});

test('the authenticated shell is hidden when printing', function () {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($layout)
        ->toContain('print:hidden')
        ->toContain('print:pl-0');
});

test('status colors use darker light-mode ink tokens', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $badge = file_get_contents(resource_path('views/components/status-badge.blade.php'));

    expect($css)
        ->toContain('--color-brand-ink: #1F6FA8;')
        ->toContain('--color-success-ink: #1E6B46;')
        ->toContain('--color-warning-ink: #7A4E12;')
        ->toContain('--color-danger-ink: #9B2226;')
        ->toContain('--color-violet-ink: #4C3AA8;')
        ->toContain('.dark .text-brand');

    expect($badge)
        ->toContain('text-brand-ink dark:text-brand')
        ->toContain('text-success-ink dark:text-success')
        ->toContain('text-warning-ink dark:text-warning');
});

test('mobile navigation uses a single off-canvas drawer', function () {
    $navigation = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

    expect($navigation)
        ->toContain('aria-label="Primary navigation"')
        ->not->toContain('Mobile navigation');
});
