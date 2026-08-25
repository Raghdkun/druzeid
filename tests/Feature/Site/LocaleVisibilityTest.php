<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Setting;
use App\Support\Locale;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Page::factory()->home()->create();
});

it('shows all locales in the switcher by default', function () {
    $this->get('/')->assertInertia(fn (Assert $p) => $p->has('locales', count(Locale::codes())));
});

it('hides disabled locales from the switcher', function () {
    Setting::put('locales.enabled', ['ar', 'en', 'fr'], 'general');

    $this->get('/')->assertInertia(function (Assert $p) {
        $codes = collect($p->toArray()['props']['locales'])->pluck('code');

        expect($codes)->toContain('ar')->toContain('en')->toContain('fr')
            ->and($codes)->not->toContain('he')
            ->and($codes)->not->toContain('de');
    });
});

it('keeps hidden-locale routes working and shows the active locale', function () {
    Setting::put('locales.enabled', ['ar', 'en'], 'general');

    // The hidden /he still resolves (routes are untouched)...
    $this->get('/he')->assertOk()->assertInertia(function (Assert $p) {
        $codes = collect($p->toArray()['props']['locales'])->pluck('code');

        // ...and the switcher includes the active (hidden) locale plus the
        // visible ones, so the visitor still sees where they are.
        expect($codes)->toContain('he')->toContain('ar')->toContain('en');
    });
});

it('always keeps the default locale visible even if omitted', function () {
    Setting::put('locales.enabled', ['en'], 'general');

    expect(Locale::visibleCodes())->toContain('ar');
});
