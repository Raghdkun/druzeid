<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/** Attach a hero_slider section with the given slides to the home page. */
function homeWithSlides(array $slides): void
{
    Page::factory()->home()->create()->sections()->create([
        'type' => 'hero_slider',
        'payload' => ['slides' => $slides],
        'sort' => 0,
        'is_visible' => true,
    ]);
}

it('renders a manual slide with its own image, title and buttons', function () {
    $media = Media::factory()->create();

    homeWithSlides([[
        'kind' => 'manual',
        'media_id' => $media->id,
        'title' => ['ar' => 'شريحة يدوية'],
        'subtitle' => ['ar' => 'وصف الشريحة'],
        'actions' => [['label' => ['ar' => 'اعرف المزيد'], 'href' => '#about']],
    ]]);

    $this->get('/')->assertOk()->assertInertia(function (Assert $p) {
        $slides = collect($p->toArray()['props']['page']['sections'])->firstWhere('type', 'hero_slider')['slides'];

        expect($slides)->toHaveCount(1);
        expect($slides[0]['kind'])->toBe('manual')
            ->and($slides[0]['title'])->toBe('شريحة يدوية')
            ->and($slides[0]['subtitle'])->toBe('وصف الشريحة')
            ->and($slides[0]['image'])->not->toBeNull()
            ->and($slides[0]['actions'][0]['href'])->toBe('#about');
    });
});

it('hydrates dynamic article, category and page slides from their references', function () {
    $article = Article::factory()->create([
        'slug' => 'slide-article',
        'title' => ['ar' => 'خبر الشريحة'],
        'excerpt' => ['ar' => 'مقتطف الخبر'],
        'featured_media_id' => Media::factory()->create()->id,
    ]);
    $category = ArticleCategory::factory()->create([
        'name' => ['ar' => 'تصنيف الشريحة'],
        'image_media_id' => Media::factory()->create()->id,
    ]);
    $refPage = Page::factory()->create([
        'slug' => 'about-slide',
        'title' => ['ar' => 'صفحة الشريحة'],
        'hero_media_id' => Media::factory()->create()->id,
    ]);

    homeWithSlides([
        ['kind' => 'article', 'ref_id' => $article->id, 'actions' => []],
        ['kind' => 'category', 'ref_id' => $category->id, 'actions' => []],
        ['kind' => 'page', 'ref_id' => $refPage->id, 'actions' => []],
    ]);

    $this->get('/')->assertOk()->assertInertia(function (Assert $p) {
        $slides = collect($p->toArray()['props']['page']['sections'])->firstWhere('type', 'hero_slider')['slides'];

        expect($slides)->toHaveCount(3);

        expect($slides[0]['title'])->toBe('خبر الشريحة')
            ->and($slides[0]['subtitle'])->toBe('مقتطف الخبر')
            ->and($slides[0]['image'])->not->toBeNull()
            ->and($slides[0]['href'])->toBe('/news/slide-article');

        expect($slides[1]['title'])->toBe('تصنيف الشريحة')
            ->and($slides[1]['image'])->not->toBeNull();

        expect($slides[2]['title'])->toBe('صفحة الشريحة')
            ->and($slides[2]['href'])->toBe('/about-slide');
    });
});

it('drops a slide whose reference is missing or unpublished', function () {
    $draft = Article::factory()->draft()->create();

    homeWithSlides([
        ['kind' => 'article', 'ref_id' => 999999, 'actions' => []], // deleted
        ['kind' => 'article', 'ref_id' => $draft->id, 'actions' => []], // unpublished
    ]);

    $this->get('/')->assertOk()->assertInertia(function (Assert $p) {
        $slides = collect($p->toArray()['props']['page']['sections'])->firstWhere('type', 'hero_slider')['slides'];

        expect($slides)->toHaveCount(0);
    });
});

it('accepts a hero_slider section when an admin saves a page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/cms/pages', [
        'slug' => 'slider-page',
        'template' => 'default',
        'accent' => 'blue',
        'status' => 'published',
        'title' => ['ar' => 'صفحة السلايدر'],
        'sections' => [[
            'type' => 'hero_slider',
            'payload' => ['slides' => [['kind' => 'manual', 'title' => ['ar' => 'شريحة'], 'actions' => []]]],
            'isVisible' => true,
        ]],
    ])->assertRedirect();

    $page = Page::where('slug', 'slider-page')->firstOrFail();
    expect($page->sections()->where('type', 'hero_slider')->count())->toBe(1);
});
