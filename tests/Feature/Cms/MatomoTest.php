<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\SiteChrome;
use App\Support\Matomo;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Page::factory()->home()->create();
    $this->admin = User::factory()->create(['role' => 'admin']);
});

/** Configure Matomo the way an admin would, storing the token encrypted. */
function configureMatomo(string $token = 'secret-token-123'): void
{
    Setting::put('matomo.enabled', true, 'analytics');
    Setting::put('matomo.url', 'https://matomo.druze.id/matomo/', 'analytics');
    Setting::put('matomo.site_id', 1, 'analytics');
    Setting::put('matomo.token', Matomo::encryptToken($token), 'analytics');
    SiteChrome::flush();
}

it('stores matomo config and encrypts the token at rest', function () {
    $this->actingAs($this->admin)
        ->put('/cms/settings', [
            'siteShortName' => ['ar' => 'اسم'],
            'matomoEnabled' => true,
            'matomoUrl' => 'https://matomo.druze.id/matomo/',
            'matomoSiteId' => 1,
            'matomoToken' => 'my-secret-token',
        ])
        ->assertRedirect();

    // Stored value is ciphertext, not the raw token; the helper decrypts it.
    expect(Setting::get('matomo.token'))->not->toBe('my-secret-token')
        ->and(Setting::get('matomo.token'))->not->toContain('my-secret-token')
        ->and(Matomo::token())->toBe('my-secret-token')
        ->and(Matomo::siteId())->toBe(1)
        ->and(Matomo::enabled())->toBeTrue();
});

it('never sends the raw token back to the settings page', function () {
    configureMatomo('top-secret');

    $response = $this->actingAs($this->admin)->get('/cms/settings');

    $response->assertInertia(fn ($p) => $p
        ->where('settings.matomoTokenSet', true)
        ->missing('settings.matomoToken')
    );
    // The ciphertext/plaintext token must not appear anywhere in the payload.
    expect($response->getContent())->not->toContain('top-secret');
});

it('keeps the stored token when the field is left blank', function () {
    configureMatomo('keep-me');

    $this->actingAs($this->admin)
        ->put('/cms/settings', ['siteShortName' => ['ar' => 'اسم'], 'matomoToken' => ''])
        ->assertRedirect();

    expect(Matomo::token())->toBe('keep-me');
});

it('clears the token with the clear flag', function () {
    configureMatomo('remove-me');

    $this->actingAs($this->admin)
        ->put('/cms/settings', ['siteShortName' => ['ar' => 'اسم'], 'matomoTokenClear' => true])
        ->assertRedirect();

    expect(Matomo::token())->toBeNull();
});

it('emits the tracker on public pages only when enabled', function () {
    configureMatomo();

    $this->get('/')
        ->assertSee('matomo.js', false)
        ->assertSee('setSiteId', false);

    // Not on the login (non-site) page.
    $this->get('/login')->assertDontSee('matomo.js', false);
});

it('loads the tag manager container instead of the plain tracker when a container id is set', function () {
    configureMatomo();
    Setting::put('matomo.container_id', 'rkUM09pP', 'analytics');
    SiteChrome::flush();

    $this->get('/')
        ->assertSee('container_rkUM09pP.js', false)
        ->assertSee('mtm.Start', false)
        // The plain tracker bootstrap is not emitted in container mode.
        ->assertDontSee('matomo.js', false);
});

it('stores and returns the tag manager container id', function () {
    $this->actingAs($this->admin)
        ->put('/cms/settings', [
            'siteShortName' => ['ar' => 'اسم'],
            'matomoEnabled' => true,
            'matomoUrl' => 'https://matomo.druze.id/matomo/',
            'matomoSiteId' => 1,
            'matomoContainerId' => 'rkUM09pP',
        ])
        ->assertRedirect();

    expect(Matomo::containerId())->toBe('rkUM09pP');

    $this->actingAs($this->admin)->get('/cms/settings')->assertInertia(fn ($p) => $p
        ->where('settings.matomoContainerId', 'rkUM09pP')
    );
});

it('rejects a non-alphanumeric container id', function () {
    $this->actingAs($this->admin)
        ->put('/cms/settings', [
            'siteShortName' => ['ar' => 'اسم'],
            'matomoEnabled' => true,
            'matomoUrl' => 'https://matomo.druze.id/matomo/',
            'matomoSiteId' => 1,
            'matomoContainerId' => '../evil.js',
        ])
        ->assertSessionHasErrors('matomoContainerId');
});

it('does not emit the tracker when disabled', function () {
    Setting::put('matomo.enabled', false, 'analytics');
    Setting::put('matomo.url', 'https://matomo.druze.id/matomo/', 'analytics');
    Setting::put('matomo.site_id', 1, 'analytics');
    SiteChrome::flush();

    $this->get('/')->assertDontSee('matomo.js', false);
});

it('allows the matomo host in the CSP when enabled', function () {
    configureMatomo();

    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('https://matomo.druze.id')
        ->and($csp)->toContain('script-src');
    // The host is added to script/img/connect, not to a wildcard default-src.
    expect($csp)->toContain("default-src 'self'");
});

it('renders the analytics dashboard from faked Matomo reports', function () {
    configureMatomo();

    Http::fake(function (Request $request) {
        $data = $request->data();
        $method = $data['method'] ?? '';
        $period = $data['period'] ?? '';

        if ($method === 'VisitsSummary.get' && $period === 'range') {
            return Http::response(['nb_visits' => 100, 'nb_uniq_visitors' => 80, 'nb_actions' => 300, 'bounce_rate' => '40%', 'avg_time_on_site' => 120]);
        }
        if ($method === 'VisitsSummary.get' && $period === 'day') {
            return Http::response(['2026-07-01' => ['nb_visits' => 10], '2026-07-02' => ['nb_visits' => 20]]);
        }
        if ($method === 'Actions.get') {
            return Http::response(['nb_pageviews' => 250]);
        }
        if ($method === 'Actions.getPageUrls') {
            return Http::response([['label' => '/home', 'nb_hits' => 50]]);
        }

        return Http::response([['label' => 'Direct', 'nb_visits' => 60]]);
    });

    $this->actingAs($this->admin)->get('/cms/analytics')->assertInertia(fn ($p) => $p
        ->component('cms/analytics/index')
        ->where('configured', true)
        ->where('reachable', true)
        ->where('summary.visits', 100)
        ->where('summary.pageviews', 250)
        ->has('series', 2)
        ->has('topPages', 1)
    );

    // The token was POSTed (in the body), never in the query string / URL.
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'matomo.druze.id')
        && ! str_contains($r->url(), 'secret-token')
        && $r['token_auth'] === 'secret-token-123');
});

it('shows the not-configured state when matomo is unset', function () {
    $this->actingAs($this->admin)->get('/cms/analytics')->assertInertia(fn ($p) => $p
        ->component('cms/analytics/index')
        ->where('configured', false)
    );
});
