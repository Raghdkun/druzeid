<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\SiteIcons;
use App\Support\Branding;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Page::factory()->home()->create();
});

/**
 * Store a real square PNG in the fake public disk and return its media row.
 */
function iconMedia(int $size = 512): Media
{
    $gd = imagecreatetruecolor($size, $size);
    ob_start();
    imagepng($gd);
    Storage::disk('public')->put('media/icon-source.png', (string) ob_get_clean());
    imagedestroy($gd);

    return Media::factory()->create([
        'path' => 'media/icon-source.png',
        'kind' => 'image',
        'mime' => 'image/png',
        'width' => $size,
        'height' => $size,
    ]);
}

it('falls back to the bundled icon when none is configured', function () {
    expect(Branding::hasCustomIcon())->toBeFalse();

    $this->get('/')
        ->assertOk()
        ->assertSee('href="/favicon.svg"', false);
});

it('generates the icon set from a source image', function () {
    Storage::fake('public');

    $paths = app(SiteIcons::class)->generate(iconMedia());

    expect(array_keys($paths))->toBe(SiteIcons::SIZES);

    foreach ($paths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeTrue();
    }
});

it('emits the generated icons and theme colour in the document head', function () {
    Storage::fake('public');

    $paths = app(SiteIcons::class)->generate(iconMedia());
    Setting::put('site.icon_paths', $paths, 'branding');
    Setting::put('site.theme_color', '#123456', 'branding');

    $this->get('/')
        ->assertOk()
        ->assertSee('rel="apple-touch-icon"', false)
        ->assertSee('sizes="32x32"', false)
        ->assertSee('content="#123456"', false)
        ->assertDontSee('href="/favicon.svg"', false);
});

it('serves a web manifest built from the branding settings', function () {
    Setting::put('site.short_name', ['ar' => 'الهوية الدرزية'], 'general');
    Setting::put('site.theme_color', '#00843D', 'branding');

    $response = $this->get('/site.webmanifest')->assertOk();

    expect($response->json('name'))->toBe('الهوية الدرزية')
        ->and($response->json('theme_color'))->toBe('#00843D')
        ->and($response->json('display'))->toBe('standalone')
        ->and($response->json('start_url'))->toBe('/');
});

it('uses the default seo description when a page has none', function () {
    // Strip the page's own description so the site-wide default applies.
    $page = Page::query()->firstOrFail();
    $page->setTranslations('excerpt', []);
    $page->setTranslations('seo_description', []);
    $page->save();

    Setting::put('site.seo_description', ['ar' => 'وصف افتراضي للموقع'], 'seo');

    $this->get('/')
        ->assertOk()
        ->assertSee('وصف افتراضي للموقع', false);
});

it('lets an admin save branding settings and regenerates the icons', function () {
    Storage::fake('public');
    $media = iconMedia();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/cms/settings', [
            'siteShortName' => ['ar' => 'اسم'],
            'themeColor' => '#C8102E',
            'iconMediaId' => $media->id,
        ])
        ->assertRedirect();

    expect(Setting::get('site.icon_media_id'))->toBe($media->id)
        ->and(Setting::get('site.theme_color'))->toBe('#C8102E')
        ->and(Branding::hasCustomIcon())->toBeTrue();
});

it('rejects a theme colour that is not a hex value', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/cms/settings', ['themeColor' => 'red'])
        ->assertSessionHasErrors('themeColor');
});

it('exposes the home url in the active locale', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('homeUrl', '/'));
    $this->get('/en')->assertInertia(fn (Assert $page) => $page->where('homeUrl', '/en'));
    $this->get('/fr')->assertInertia(fn (Assert $page) => $page->where('homeUrl', '/fr'));
});

it('exposes the direction and html lang for the active locale', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('dir', 'rtl')
        ->where('htmlLang', 'ar')
    );

    $this->get('/en')->assertInertia(fn (Assert $page) => $page
        ->where('dir', 'ltr')
        ->where('htmlLang', 'en-GB')
    );
});
