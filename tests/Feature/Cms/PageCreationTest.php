<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\User;

it('creates a new page from any template and serves it publicly', function (string $template) {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post('/cms/pages', [
        'slug' => 'brand-new',
        'template' => $template,
        'status' => 'published',
        'accent' => 'green',
        'title' => ['ar' => 'صفحة جديدة', 'en' => 'Brand New'],
        'excerpt' => ['ar' => 'وصف', 'en' => 'Description'],
        'sections' => [
            [
                'type' => 'rich_text',
                'payload' => ['html' => ['ar' => '<p>محتوى جديد</p>', 'en' => '<p>New content</p>']],
                'isVisible' => true,
            ],
        ],
    ])->assertRedirect();

    $page = Page::where('slug', 'brand-new')->firstOrFail();
    expect($page->template)->toBe($template);

    // Reachable and rendered on the public site, in both locales.
    $this->get('/brand-new')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('site/page')
            ->where('page.title', 'صفحة جديدة')
            ->where('page.sections.0.type', 'rich_text')
            ->where('page.sections.0.html', '<p>محتوى جديد</p>'));

    $this->get('/en/brand-new')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('page.title', 'Brand New')
            ->where('page.sections.0.html', '<p>New content</p>'));
})->with(['default', 'contact', 'under_construction']);

it('creates a page with every structured section type', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post('/cms/pages', [
        'slug' => 'kitchen-sink',
        'template' => 'default',
        'status' => 'published',
        'title' => ['ar' => 'كل الأقسام'],
        'sections' => [
            ['type' => 'hero', 'payload' => ['title' => ['ar' => 'واجهة'], 'media_ids' => []], 'isVisible' => true],
            ['type' => 'rich_text', 'payload' => ['html' => ['ar' => '<p>نص</p>']], 'isVisible' => true],
            ['type' => 'card_grid', 'payload' => ['heading' => ['ar' => 'شبكة'], 'cards' => []], 'isVisible' => true],
            ['type' => 'gallery', 'payload' => ['media_ids' => []], 'isVisible' => true],
            ['type' => 'accordion', 'payload' => ['heading' => ['ar' => 'ط'], 'items' => []], 'isVisible' => true],
            ['type' => 'under_construction', 'payload' => ['domain' => 'www.x.com', 'html' => ['ar' => '<p>قريبا</p>']], 'isVisible' => true],
            ['type' => 'contact', 'payload' => ['email' => 'a@b.com', 'phones' => [], 'html' => ['ar' => '<p>ص</p>'], 'showForm' => true], 'isVisible' => true],
        ],
    ])->assertRedirect();

    $page = Page::where('slug', 'kitchen-sink')->firstOrFail();
    expect($page->sections)->toHaveCount(7);

    $this->get('/kitchen-sink')->assertOk();
});
