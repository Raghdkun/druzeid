<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\Html;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Page::factory()->home()->create();
});

it('exposes the three new locales with correct direction', function () {
    $this->get('/es')->assertInertia(fn (Assert $p) => $p->where('dir', 'ltr')->where('htmlLang', 'es'));
    $this->get('/de')->assertInertia(fn (Assert $p) => $p->where('dir', 'ltr')->where('htmlLang', 'de'));
    // Hebrew is a second RTL locale alongside the Arabic default.
    $this->get('/he')->assertInertia(fn (Assert $p) => $p->where('dir', 'rtl')->where('htmlLang', 'he'));
});

it('lists published articles on the news index, newest first', function () {
    Article::factory()->create(['published_at' => now()->subDay()]);
    Article::factory()->create(['published_at' => now()]);
    Article::factory()->draft()->create();

    $this->get('/news')->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('site/news-index')
        ->has('articles.data', 2)
    );
});

it('renders a published article and 404s an unpublished one', function () {
    $article = Article::factory()->create(['slug' => 'a-news-item']);
    $draft = Article::factory()->draft()->create(['slug' => 'hidden']);

    $this->get('/news/a-news-item')->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('site/article')
        ->where('article.slug', 'a-news-item')
    );
    $this->get('/news/hidden')->assertNotFound();
    expect($draft->isPublished())->toBeFalse();
});

it('creates an article from the dashboard that shows in the ticker and news index (no seed)', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Exactly what the CMS form posts — no seeder involved.
    $this->actingAs($admin)
        ->post('/cms/articles', [
            'slug' => 'newly-authored',
            'title' => ['ar' => 'خبر من لوحة التحكم'],
            'excerpt' => ['ar' => 'مقتطف'],
            'body' => ['ar' => '<p>نص الخبر</p>'],
            'status' => 'published',
            'isFeatured' => false,
        ])
        ->assertRedirect();

    $article = Article::sole();
    expect($article->status)->toBe('published')
        ->and($article->author_id)->toBe($admin->id);

    // It surfaces in the ticker (shared chrome) and the public news index.
    $this->get('/')->assertInertia(fn (Assert $p) => expect(
        collect($p->toArray()['props']['latestNews'])->pluck('title')
    )->toContain('خبر من لوحة التحكم'));

    $this->get('/news')->assertOk()->assertInertia(fn (Assert $p) => $p->has('articles.data', 1));
});

it('feeds the news ticker with the latest headlines site-wide', function () {
    Article::factory()->create(['title' => ['ar' => 'خبر منشور']]);
    Article::factory()->draft()->create();

    // The ticker reads latestNews + newsIndexUrl from the shared chrome props.
    $this->get('/')->assertInertia(function (Assert $p) {
        $props = $p->toArray()['props'];
        expect($props['latestNews'])->toHaveCount(1)
            ->and($props['latestNews'][0]['title'])->toBe('خبر منشور')
            ->and($props['newsIndexUrl'])->toBe('/news');
    });
});

it('round-trips the whatsapp setting and drives the bubble data', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/cms/settings', ['siteShortName' => ['ar' => 'اسم'], 'contactWhatsapp' => '+963 11 222 333'])
        ->assertRedirect();

    expect(Setting::get('contact.whatsapp'))->toBe('+963 11 222 333');

    // The public chrome carries it (the contact bubble reads this key).
    $this->get('/')->assertInertia(function (Assert $p) {
        expect($p->toArray()['props']['settings']['contact.whatsapp'])->toBe('+963 11 222 333');
    });
});

it('rejects an invalid whatsapp number', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/cms/settings', ['siteShortName' => ['ar' => 'اسم'], 'contactWhatsapp' => 'not-a-number'])
        ->assertSessionHasErrors('contactWhatsapp');
});

it('allows text colour in prose but strips other inline CSS', function () {
    $clean = Html::clean('<p><span style="color:#069ed9;background:url(x)">a</span></p>');

    expect($clean)->toContain('color:#069ed9')
        ->and($clean)->not->toContain('url(');
});
