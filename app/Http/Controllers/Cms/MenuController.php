<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Rules\PublicUrl;
use App\Services\SiteChrome;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $menus = Menu::query()
            ->with(['rootItems'])
            ->get()
            ->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'key' => $menu->key,
                'name' => $menu->name,
                'items' => $menu->rootItems->map($this->presentItem(...))->all(),
            ]);

        return inertia('cms/menus/index', [
            'menus' => $menus,
            'pages' => Page::query()
                ->orderBy('sort')
                ->get(['id', 'slug', 'title'])
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->getTranslation('title', Locale::default()),
                ]),
            'locales' => array_map(fn (string $code) => [
                'code' => $code,
                'name' => Locale::meta($code)['name'],
                'dir' => Locale::dir($code),
                'default' => $code === Locale::default(),
            ], Locale::codes()),
        ]);
    }

    /**
     * Replace a menu's item tree wholesale from the editor's ordered payload.
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['present', 'array'],
            'items.*.label' => ['required', 'array'],
            'items.*.label.*' => ['nullable', 'string', 'max:120'],
            'items.*.pageId' => ['nullable', 'integer', 'exists:pages,id'],
            'items.*.url' => ['nullable', 'string', 'max:2048', new PublicUrl],
            'items.*.opensNewTab' => ['boolean'],
            'items.*.children' => ['array'],
            'items.*.children.*.label' => ['required', 'array'],
            'items.*.children.*.pageId' => ['nullable', 'integer', 'exists:pages,id'],
            'items.*.children.*.url' => ['nullable', 'string', 'max:2048', new PublicUrl],
            'items.*.children.*.opensNewTab' => ['boolean'],
        ]);

        DB::transaction(function () use ($menu, $validated): void {
            $menu->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                $parent = $this->createItem($menu, null, $item, $index);

                foreach ($item['children'] ?? [] as $childIndex => $child) {
                    $this->createItem($menu, $parent->id, $child, $childIndex);
                }
            }
        });

        SiteChrome::flush();

        return back()->with('success', __('Menu saved.'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createItem(Menu $menu, ?int $parentId, array $data, int $sort): MenuItem
    {
        return $menu->items()->create([
            'parent_id' => $parentId,
            'label' => array_filter($data['label'], fn ($v) => filled($v)),
            'page_id' => $data['pageId'] ?? null,
            'url' => $data['url'] ?? null,
            'opens_new_tab' => $data['opensNewTab'] ?? false,
            'sort' => $sort * 10,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentItem(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->getTranslations('label'),
            'pageId' => $item->page_id,
            'url' => $item->url,
            'opensNewTab' => $item->opens_new_tab,
            'children' => $item->children->map($this->presentItem(...))->all(),
        ];
    }
}
