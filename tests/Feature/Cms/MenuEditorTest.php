<?php

declare(strict_types=1);

use App\Models\Menu;
use App\Models\Page;
use App\Models\User;

it('adds a new menu item with an external URL, new tab, and a nested child', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $menu = Menu::create(['key' => 'primary', 'name' => 'Primary']);
    $page = Page::factory()->create(['slug' => 'about']);

    $this->actingAs($admin)->put("/cms/menus/{$menu->id}", [
        'items' => [
            [
                'label' => ['ar' => 'رابط خارجي', 'en' => 'External'],
                'url' => 'https://example.com',
                'opensNewTab' => true,
                'children' => [
                    ['label' => ['ar' => 'فرعي'], 'pageId' => $page->id, 'opensNewTab' => false, 'children' => []],
                ],
            ],
        ],
    ])->assertRedirect();

    $item = $menu->items()->whereNull('parent_id')->with('children')->firstOrFail();

    expect($item->url)->toBe('https://example.com')
        ->and($item->opens_new_tab)->toBeTrue()
        ->and($item->getTranslation('label', 'en'))->toBe('External')
        ->and($item->children)->toHaveCount(1)
        ->and($item->children->first()->page_id)->toBe($page->id);
});

it('replaces the whole tree so deletions and reorders persist', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $menu = Menu::create(['key' => 'primary', 'name' => 'Primary']);
    $menu->items()->create(['label' => ['ar' => 'قديم'], 'sort' => 0]);

    // Save a tree that no longer contains the old item.
    $this->actingAs($admin)->put("/cms/menus/{$menu->id}", [
        'items' => [
            ['label' => ['ar' => 'جديد'], 'url' => null, 'pageId' => null, 'opensNewTab' => false, 'children' => []],
        ],
    ])->assertRedirect();

    $labels = $menu->items()->get()->map(fn ($i) => $i->getTranslation('label', 'ar'));

    expect($labels)->toHaveCount(1)->and($labels->first())->toBe('جديد');
});

it('forbids non-admins from editing menus', function () {
    $menu = Menu::create(['key' => 'primary', 'name' => 'Primary']);

    $this->put("/cms/menus/{$menu->id}", ['items' => []])->assertRedirect('/login');

    // Content editors manage menus too (can:manage-content); a viewer cannot —
    // covered by the existing authorization suite. Here we assert the guest gate.
});
