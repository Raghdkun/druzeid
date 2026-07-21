<?php

declare(strict_types=1);

use App\Models\Page;

it('serves the default locale from the bare root without a prefix', function () {
    Page::factory()->home()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/page')
            ->where('locale', 'ar')
            ->where('dir', 'rtl'));
});

it('serves each non-default locale from its prefix with the right direction', function (string $prefix, string $locale, string $dir) {
    Page::factory()->home()->create();

    $this->get($prefix)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', $locale)
            ->where('dir', $dir));
})->with([
    'english' => ['/en', 'en', 'ltr'],
    'french' => ['/fr', 'fr', 'ltr'],
    'dutch' => ['/nl', 'nl', 'ltr'],
]);

it('resolves a page from the catch-all in the active locale', function () {
    Page::factory()->home()->create();
    Page::factory()->create(['slug' => 'org-about-us']);

    $this->get('/org-about-us')->assertOk();
    $this->get('/en/org-about-us')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('does not let a locale prefix be mistaken for a page slug', function () {
    Page::factory()->home()->create();
    // A page literally slugged "en" must not shadow the English home.
    Page::factory()->create(['slug' => 'en-page']);

    $this->get('/en')->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('404s an unknown slug', function () {
    Page::factory()->home()->create();

    $this->get('/does-not-exist')->assertNotFound();
});

it('does not expose a draft page', function () {
    Page::factory()->home()->create();
    Page::factory()->draft()->create(['slug' => 'secret']);

    $this->get('/secret')->assertNotFound();
});

it('sets the html lang and dir attributes per locale', function () {
    Page::factory()->home()->create();

    $this->get('/')->assertSee('dir="rtl"', false)->assertSee('lang="ar"', false);
    $this->get('/en')->assertSee('dir="ltr"', false)->assertSee('lang="en-GB"', false);
});
