<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\User;

function admin(): User
{
    return User::factory()->create(['role' => User::ROLE_ADMIN]);
}

function editor(): User
{
    return User::factory()->create(['role' => User::ROLE_EDITOR]);
}

it('redirects guests away from the CMS', function () {
    $this->get('/cms')->assertRedirect('/login');
});

it('lets an editor open the CMS overview', function () {
    $this->actingAs(editor())->get('/cms')->assertOk()
        ->assertInertia(fn ($page) => $page->component('cms/overview'));
});

it('creates a page with translated fields and sections', function () {
    $this->actingAs(admin())
        ->post('/cms/pages', [
            'slug' => 'new-page',
            'template' => 'default',
            'accent' => 'green',
            'status' => 'published',
            'title' => ['ar' => 'صفحة جديدة', 'en' => 'New Page'],
            'excerpt' => ['ar' => 'وصف'],
            'sections' => [
                [
                    'type' => 'rich_text',
                    'payload' => ['blocks' => [['kind' => 'paragraph', 'text' => ['ar' => 'نص']]]],
                    'isVisible' => true,
                ],
            ],
        ])
        ->assertRedirect();

    $page = Page::where('slug', 'new-page')->firstOrFail();

    expect($page->getTranslation('title', 'ar'))->toBe('صفحة جديدة')
        ->and($page->getTranslation('title', 'en'))->toBe('New Page')
        ->and($page->sections)->toHaveCount(1)
        ->and($page->status)->toBe('published');
});

it('requires an Arabic title', function () {
    $this->actingAs(admin())
        ->post('/cms/pages', [
            'slug' => 'no-title',
            'template' => 'default',
            'status' => 'draft',
            'title' => ['en' => 'English only'],
            'sections' => [],
        ])
        ->assertSessionHasErrors('title.ar');
});

it('rejects a duplicate slug', function () {
    Page::factory()->create(['slug' => 'taken']);

    $this->actingAs(admin())
        ->post('/cms/pages', [
            'slug' => 'taken',
            'template' => 'default',
            'status' => 'draft',
            'title' => ['ar' => 'x'],
            'sections' => [],
        ])
        ->assertSessionHasErrors('slug');
});

it('replaces the section list wholesale on update', function () {
    $page = Page::factory()->create();
    $page->sections()->create(['type' => 'rich_text', 'payload' => ['blocks' => []], 'sort' => 0]);

    $this->actingAs(editor())
        ->put("/cms/pages/{$page->id}", [
            'slug' => $page->slug,
            'template' => 'default',
            'status' => 'published',
            'title' => ['ar' => 'محدث'],
            'sections' => [
                ['type' => 'hero', 'payload' => ['title' => ['ar' => 'ه']], 'isVisible' => true],
                ['type' => 'gallery', 'payload' => ['media_ids' => []], 'isVisible' => true],
            ],
        ])
        ->assertRedirect();

    $page->refresh();
    expect($page->sections)->toHaveCount(2)
        ->and($page->sections->pluck('type')->all())->toBe(['hero', 'gallery']);
});

it('deletes a page', function () {
    $page = Page::factory()->create();

    $this->actingAs(admin())->delete("/cms/pages/{$page->id}")->assertRedirect();

    expect(Page::find($page->id))->toBeNull();
});

it('rejects an unknown section type', function () {
    $this->actingAs(admin())
        ->post('/cms/pages', [
            'slug' => 'bad-section',
            'template' => 'default',
            'status' => 'draft',
            'title' => ['ar' => 'x'],
            'sections' => [['type' => 'not_a_type', 'payload' => [], 'isVisible' => true]],
        ])
        ->assertSessionHasErrors('sections.0.type');
});
