<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Page;

it('serves a sitemap with an entry per page per locale', function () {
    Page::factory()->home()->create();
    Page::factory()->create(['slug' => 'org-about-us']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    // 2 pages x 4 locales = 8 <loc> entries.
    expect(substr_count($response->getContent(), '<loc>'))->toBe(8);

    // hreflang alternates present, including x-default.
    $response->assertSee('hreflang="ar"', false)
        ->assertSee('hreflang="en-GB"', false)
        ->assertSee('hreflang="x-default"', false)
        ->assertSee('/en/org-about-us', false)
        ->assertSee('<changefreq>', false)
        ->assertSee('<priority>', false);
});

it('lists a page hero image in the sitemap', function () {
    $media = Media::factory()->create(['alt' => ['ar' => 'شعار']]);
    Page::factory()->home()->create(['hero_media_id' => $media->id]);

    $this->get('/sitemap.xml')
        ->assertSee('<image:image>', false)
        ->assertSee('<image:title>شعار</image:title>', false);
});

it('serves robots.txt pointing at the sitemap and disallowing the CMS', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /cms')
        ->assertSee('Sitemap:');
});

it('renders a branded 404 inside the site shell', function () {
    Page::factory()->home()->create();

    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('site/error')
            ->where('status', 404)
            ->has('menus')
            ->has('settings'));
});

it('keeps the CMS 404 out of the public shell', function () {
    // An unknown CMS path 404s without adopting the public error component —
    // the handler only brands public routes.
    $response = $this->get('/cms/no-such-thing');

    $response->assertNotFound();
    expect($response->getContent())->not->toContain('site/error');
});
