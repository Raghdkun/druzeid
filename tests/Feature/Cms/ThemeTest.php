<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use App\Support\Theme;

it('emits no theme css by default', function () {
    expect(Theme::cssVariables('ar'))->toBe('');
});

it('maps a brand colour to every token it drives, with derived shades', function () {
    Setting::put('theme.color.blue', '#123456', 'theme');

    $css = Theme::cssVariables('ar');

    expect($css)->toContain('--color-druze-blue: #123456;')
        ->and($css)->toContain('--primary: #123456;')
        ->and($css)->toContain('--ring: #123456;')
        ->and($css)->toContain('--chart-1: #123456;')
        ->and($css)->toContain('--color-druze-blue-deep:')
        ->and($css)->toContain('--color-druze-blue-tint:');
});

it('derives neutral shades from ink and surface', function () {
    Setting::put('theme.color.ink', '#202020', 'theme');
    Setting::put('theme.color.surface', '#ffffff', 'theme');

    $css = Theme::cssVariables('ar');

    expect($css)->toContain('--foreground: #202020;')
        ->and($css)->toContain('--color-ink-muted:')
        ->and($css)->toContain('--background: #ffffff;')
        ->and($css)->toContain('--color-surface-alt:')
        ->and($css)->toContain('--color-hairline:');
});

it('sets the active-locale fonts only when changed from the default', function () {
    Setting::put('theme.font.en.display', 'inter', 'theme');

    expect(Theme::cssVariables('en'))->toContain("--font-display: 'Inter'");
    // A different locale is unaffected.
    expect(Theme::cssVariables('ar'))->not->toContain('Inter');
});

it('stores a theme via the dashboard and drops values equal to defaults', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/cms/theme', [
        'colors' => ['blue' => '#0000ff', 'green' => Theme::DEFAULTS['green']],
        'fonts' => ['ar' => ['display' => 'tajawal', 'body' => Theme::DEFAULT_BODY]],
    ])->assertRedirect();

    expect(Setting::get('theme.color.blue'))->toBe('#0000ff')
        ->and(Setting::get('theme.color.green'))->toBeNull()
        ->and(Setting::get('theme.font.ar.display'))->toBe('tajawal')
        ->and(Setting::get('theme.font.ar.body'))->toBeNull();
});

it('rejects an invalid colour and an unknown font', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/cms/theme', [
        'colors' => ['blue' => 'not-a-hex'],
    ])->assertSessionHasErrors('colors.blue');

    $this->actingAs($admin)->put('/cms/theme', [
        'fonts' => ['ar' => ['display' => 'comic-sans']],
    ])->assertSessionHasErrors('fonts.ar.display');
});

it('forbids non-admins from the theme editor', function () {
    $this->get('/cms/theme')->assertRedirect('/login');

    $editor = User::factory()->create(['role' => 'editor']);
    $this->actingAs($editor)->get('/cms/theme')->assertForbidden();
});
