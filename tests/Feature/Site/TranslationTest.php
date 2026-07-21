<?php

declare(strict_types=1);

use App\Models\Page;
use App\Support\Translate;
use Illuminate\Support\Facades\App;

it('resolves a translatable attribute for the active locale', function () {
    $page = Page::factory()->create([
        'title' => ['ar' => 'من نحن', 'en' => 'Who We Are'],
    ]);

    App::setLocale('ar');
    expect($page->fresh()->title)->toBe('من نحن');

    App::setLocale('en');
    expect($page->fresh()->title)->toBe('Who We Are');
});

it('falls back through the chain when the active locale is empty', function () {
    $page = Page::factory()->create([
        'title' => ['ar' => 'العنوان'],   // ar only
    ]);

    // French is missing → falls back to en (also missing) → default ar.
    App::setLocale('fr');
    expect($page->fresh()->title)->toBe('العنوان');
});

it('reports exact-locale presence for the CMS indicator', function () {
    $page = Page::factory()->create([
        'title' => ['ar' => 'العنوان', 'en' => 'Title'],
    ]);

    expect($page->hasTranslation('title', 'ar'))->toBeTrue()
        ->and($page->hasTranslation('title', 'fr'))->toBeFalse();
});

it('measures per-locale completeness across translatable fields', function () {
    $page = Page::factory()->create([
        'title' => ['ar' => 'x', 'en' => 'x'],
        'excerpt' => ['ar' => 'y'],
        'seo_title' => [],
        'seo_description' => [],
    ]);

    // ar has 2 of 4 translatable fields populated.
    expect($page->translationCompleteness('ar'))->toBe(0.5)
        ->and($page->translationCompleteness('en'))->toBe(0.25);
});

it('setting a scalar writes only the active locale', function () {
    App::setLocale('en');
    $page = Page::factory()->create(['title' => ['ar' => 'العنوان']]);

    $page->title = 'New English';
    $page->save();

    expect($page->getTranslations('title'))
        ->toMatchArray(['ar' => 'العنوان', 'en' => 'New English']);
});

it('prunes unsupported locales and empty values on save', function () {
    $page = Page::factory()->create();
    $page->setTranslations('title', ['ar' => 'ع', 'en' => '', 'zz' => 'x']);
    $page->save();

    expect($page->fresh()->getTranslations('title'))->toBe(['ar' => 'ع']);
});

it('collapses nested locale maps anywhere in a structure', function () {
    App::setLocale('en');

    $payload = [
        'heading' => ['ar' => 'عنوان', 'en' => 'Heading'],
        'items' => [
            ['title' => ['ar' => 'أ', 'en' => 'A'], 'url' => '/a'],
            ['title' => ['ar' => 'ب', 'en' => 'B'], 'url' => '/b'],
        ],
        'columns' => 3,
    ];

    expect(Translate::resolve($payload))->toBe([
        'heading' => 'Heading',
        'items' => [
            ['title' => 'A', 'url' => '/a'],
            ['title' => 'B', 'url' => '/b'],
        ],
        'columns' => 3,
    ]);
});

it('does not mistake an item list or config object for a locale map', function () {
    // Integer keys — a list, not a locale map.
    expect(Translate::isLocaleMap(['ar' => 'x', 'en' => 'y']))->toBeTrue()
        ->and(Translate::isLocaleMap([0 => 'x', 1 => 'y']))->toBeFalse()
        ->and(Translate::isLocaleMap(['columns' => 3]))->toBeFalse();
});
