<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Actions\Cms\SavePage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\PageRequest;
use App\Http\Resources\CmsPageResource;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\SiteChrome;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        return inertia('cms/pages/index', [
            'pages' => Page::query()
                ->withCount('sections')
                ->orderBy('sort')
                ->get()
                ->map(fn (Page $page) => CmsPageResource::summary($page)),
            'locales' => $this->localeMeta(),
        ]);
    }

    public function create(): Response
    {
        return inertia('cms/pages/edit', [
            'page' => null,
            'mediaLibrary' => $this->mediaLibrary(),
            'sectionTypes' => PageSection::TYPES,
            'references' => $this->references(),
            'templates' => $this->templates(),
            'accents' => ['blue', 'green', 'red', 'yellow', 'white'],
            'locales' => $this->localeMeta(),
        ]);
    }

    public function store(PageRequest $request, SavePage $savePage): RedirectResponse
    {
        $page = $savePage->handle(new Page, $request->validated());

        return redirect()
            ->route('cms.pages.edit', $page)
            ->with('success', __('Page created.'));
    }

    public function edit(Page $page): Response
    {
        return inertia('cms/pages/edit', [
            'page' => CmsPageResource::detail($page),
            'mediaLibrary' => $this->mediaLibrary(),
            'sectionTypes' => PageSection::TYPES,
            'references' => $this->references(),
            'templates' => $this->templates(),
            'accents' => ['blue', 'green', 'red', 'yellow', 'white'],
            'locales' => $this->localeMeta(),
        ]);
    }

    public function update(PageRequest $request, Page $page, SavePage $savePage): RedirectResponse
    {
        $savePage->handle($page, $request->validated());

        return back()->with('success', __('Page saved.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();
        SiteChrome::flush();

        return redirect()
            ->route('cms.pages.index')
            ->with('success', __('Page deleted.'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mediaLibrary(): array
    {
        return array_values(
            Media::query()
                ->latest()
                ->take(200)
                ->get()
                ->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->url,
                    'kind' => $media->kind,
                    'poster' => $media->poster_url,
                    'alt' => $media->getTranslation('alt', Locale::default()) ?? '',
                    'width' => $media->width,
                    'height' => $media->height,
                    'needsAltReview' => $media->needs_alt_review,
                ])
                ->all()
        );
    }

    /**
     * @return list<array{code: string, name: string, dir: string, default: bool}>
     */
    private function localeMeta(): array
    {
        return array_map(fn (string $code) => [
            'code' => $code,
            'name' => Locale::meta($code)['name'],
            'dir' => Locale::dir($code),
            'default' => $code === Locale::default(),
        ], Locale::codes());
    }

    /**
     * Lightweight `{id, name}` lists of the content a dynamic hero slide can
     * point at — articles, categories and pages — collapsed to the default
     * locale for the editor's reference pickers.
     *
     * @return array{articles: list<array{id: int, name: string}>, categories: list<array{id: int, name: string}>, pages: list<array{id: int, name: string}>}
     */
    private function references(): array
    {
        $default = Locale::default();

        return [
            'articles' => array_values(
                Article::query()
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->take(200)
                    ->get()
                    ->map(fn (Article $a) => [
                        'id' => $a->id,
                        'name' => $a->getTranslation('title', $default) ?: $a->slug,
                    ])
                    ->all()
            ),
            'categories' => array_values(
                ArticleCategory::query()
                    ->orderBy('sort')
                    ->get()
                    ->map(fn (ArticleCategory $c) => [
                        'id' => $c->id,
                        'name' => $c->getTranslation('name', $default) ?: $c->slug,
                    ])
                    ->all()
            ),
            'pages' => array_values(
                Page::query()
                    ->orderBy('sort')
                    ->get()
                    ->map(fn (Page $p) => [
                        'id' => $p->id,
                        'name' => $p->getTranslation('title', $default) ?: $p->slug,
                    ])
                    ->all()
            ),
        ];
    }

    /**
     * @return list<string>
     */
    private function templates(): array
    {
        return ['default', 'home', 'contact', 'under_construction'];
    }
}
