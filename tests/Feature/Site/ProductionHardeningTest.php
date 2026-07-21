<?php

declare(strict_types=1);

use App\Models\Page;

it('sends security headers on HTML responses', function () {
    Page::factory()->home()->create();

    $response = $this->get('/');

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        ->toContain('frame-ancestors');
});

it('renders SEO and Open Graph meta server-side', function () {
    Page::factory()->create([
        'slug' => 'org-about-us',
        'title' => ['ar' => 'من نحن', 'en' => 'Who We Are'],
        'excerpt' => ['en' => 'About the organization'],
    ]);

    $html = $this->get('/en/org-about-us')->getContent();

    expect($html)
        ->toContain('<title>Who We Are</title>')
        ->toContain('<meta name="description" content="About the organization">')
        ->toContain('property="og:title" content="Who We Are"')
        ->toContain('property="og:locale" content="en_GB"')
        ->toContain('rel="canonical"')
        ->toContain('hreflang="ar"')
        ->toContain('application/ld+json')
        ->toContain('"@type":"Organization"');
});
