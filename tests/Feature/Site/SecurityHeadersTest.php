<?php

declare(strict_types=1);

use App\Models\Menu;
use App\Models\User;

it('sends a nonce-based CSP with no unsafe-inline script-src', function () {
    $response = $this->get('/');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self'")
        ->and($csp)->toContain("'nonce-")
        ->and($csp)->not->toContain("script-src 'self' 'unsafe-inline'")
        ->and($csp)->toContain("object-src 'none'")
        ->and($csp)->toContain("frame-ancestors 'self'")
        ->and($csp)->toContain("base-uri 'self'")
        ->and($csp)->toContain("media-src 'self' blob:");
});

it('stamps the CSP nonce onto inline scripts in the document', function () {
    $response = $this->get('/');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([A-Za-z0-9+\/=]+)'/", $csp, $m);
    $nonce = $m[1] ?? null;

    expect($nonce)->not->toBeNull()
        ->and($response->getContent())->toContain('nonce="'.$nonce.'"');
});

it('ships the baseline hardening headers', function () {
    $response = $this->get('/');

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});

it('rejects a javascript: scheme in a menu url', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $menu = Menu::factory()->create();

    $this->actingAs($admin)
        ->put("/cms/menus/{$menu->id}", [
            'items' => [
                ['label' => ['ar' => 'خبيث'], 'url' => 'javascript:alert(1)', 'children' => []],
            ],
        ])
        ->assertSessionHasErrors('items.0.url');
});

it('accepts a relative path and an https url in a menu', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $menu = Menu::factory()->create();

    $this->actingAs($admin)
        ->put("/cms/menus/{$menu->id}", [
            'items' => [
                ['label' => ['ar' => 'داخلي'], 'url' => '/about', 'children' => []],
                ['label' => ['ar' => 'خارجي'], 'url' => 'https://example.org', 'children' => []],
            ],
        ])
        ->assertSessionHasNoErrors();
});
