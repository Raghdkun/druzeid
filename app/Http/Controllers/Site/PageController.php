<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
use App\Services\SiteChrome;
use App\Support\Locale;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly SiteChrome $chrome) {}

    public function home(): Response
    {
        $page = Page::query()
            ->published()
            ->where('template', 'home')
            ->with(['sections' => fn ($q) => $q->where('is_visible', true), 'heroMedia'])
            ->firstOrFail();

        return $this->render($page);
    }

    public function show(string $slug): Response
    {
        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->with(['sections' => fn ($q) => $q->where('is_visible', true), 'heroMedia'])
            ->firstOrFail();

        return $this->render($page);
    }

    protected function render(Page $page): Response
    {
        $locale = app()->getLocale();

        return inertia('site/page', [
            ...$this->chrome->props($locale),
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'template' => $page->template,
                'accent' => $page->accent,
                'title' => $page->getTranslation('title'),
                'excerpt' => $page->getTranslation('excerpt'),
                'heroImage' => $page->heroMedia?->toImageProps(),
                'sections' => $page->sections
                    ->map(function (PageSection $section) use ($locale) {
                        $payload = $this->resolveSlugHrefs($section->resolvedPayload($locale), $locale);

                        if ($section->type === 'hero_slider') {
                            $payload = $this->hydrateSlider($payload, $locale);
                        }

                        return [
                            'id' => $section->id,
                            'type' => $section->type,
                            ...$payload,
                        ];
                    })
                    ->all(),
            ],
            'meta' => [
                'title' => $page->getTranslation('seo_title') ?: $page->getTranslation('title'),
                'description' => $page->getTranslation('seo_description') ?: $page->getTranslation('excerpt'),
                'canonical' => $page->urlFor($locale),
                'alternates' => $this->alternates($page),
                'ogImage' => $page->heroMedia?->url ? url($page->heroMedia->url) : null,
                'ogLocale' => str_replace('-', '_', Locale::htmlLang($locale)),
                'siteName' => Setting::localized('site.short_name', $locale)
                    ?? config('app.name'),
            ],
        ]);
    }

    /**
     * Turn every `slug` reference in a section payload into a resolved,
     * locale-correct `href`. Hero actions and cards store a slug so the
     * content stays portable; the front-end only ever sees a URL.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function resolveSlugHrefs(array $payload, string $locale): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->resolveSlugHrefs($value, $locale);

                continue;
            }

            if ($key === 'slug' && is_string($value) && ! isset($payload['href'])) {
                // Relative so Inertia's client router intercepts card/hero
                // links regardless of host/port/scheme (see Locale::path).
                $payload['href'] = Locale::path('pages.show', ['slug' => $value], $locale);
            }
        }

        return $payload;
    }

    /**
     * Normalise a hero_slider payload: every slide is reduced to the uniform
     * shape the carousel consumes — `{kind, title, subtitle, image, href,
     * actions}`. Manual slides already carry a resolved title/subtitle/image
     * (locale-collapsed + media-hydrated upstream); dynamic slides resolve
     * their article/category/page reference here. Unresolvable refs (deleted or
     * unpublished) are dropped so the slider never shows an empty slide.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function hydrateSlider(array $payload, string $locale): array
    {
        $slides = is_array($payload['slides'] ?? null) ? $payload['slides'] : [];

        $payload['slides'] = array_values(array_filter(array_map(
            fn ($slide): ?array => $this->resolveSlide(is_array($slide) ? $slide : [], $locale),
            $slides
        )));

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $slide
     * @return array<string, mixed>|null
     */
    protected function resolveSlide(array $slide, string $locale): ?array
    {
        $kind = is_string($slide['kind'] ?? null) ? $slide['kind'] : 'manual';

        // Actions arrive already resolved (label locale-collapsed, href/slug
        // handled upstream); keep at most two, in order.
        $actions = array_values(array_filter(
            is_array($slide['actions'] ?? null) ? $slide['actions'] : [],
            'is_array'
        ));
        $actions = array_slice($actions, 0, 2);

        if ($kind === 'manual') {
            $image = is_array($slide['image'] ?? null) ? $slide['image'] : null;

            // A per-slide focal point overrides the media's own object-position,
            // so the same photo can be framed differently on different slides.
            if ($image !== null && is_string($slide['object_position'] ?? null) && $slide['object_position'] !== '') {
                $image['objectPosition'] = $slide['object_position'];
            }

            return [
                'kind' => 'manual',
                'title' => is_string($slide['title'] ?? null) ? $slide['title'] : null,
                'subtitle' => is_string($slide['subtitle'] ?? null) ? $slide['subtitle'] : null,
                'image' => $image,
                'href' => null,
                'actions' => $actions,
            ];
        }

        $refId = is_int($slide['ref_id'] ?? null) ? $slide['ref_id'] : null;

        $resolved = $refId === null ? null : match ($kind) {
            'article' => $this->resolveArticleSlide($refId, $locale),
            'category' => $this->resolveCategorySlide($refId, $locale),
            'page' => $this->resolvePageSlide($refId, $locale),
            default => null,
        };

        if ($resolved === null) {
            return null;
        }

        return [...$resolved, 'kind' => $kind, 'actions' => $actions];
    }

    /**
     * @return array{title: string, subtitle: ?string, image: ?array<string, mixed>, href: ?string}|null
     */
    protected function resolveArticleSlide(int $id, string $locale): ?array
    {
        $article = Article::query()->published()->find($id);

        if ($article === null) {
            return null;
        }

        $card = $article->toCardProps($locale);

        return [
            'title' => (string) $card['title'],
            'subtitle' => $card['excerpt'],
            'image' => $card['image'],
            'href' => $card['href'],
        ];
    }

    /**
     * @return array{title: string, subtitle: ?string, image: ?array<string, mixed>, href: ?string}|null
     */
    protected function resolveCategorySlide(int $id, string $locale): ?array
    {
        $category = ArticleCategory::query()->with('featuredMedia')->find($id);

        if ($category === null) {
            return null;
        }

        return [
            'title' => $category->getTranslation('name', $locale) ?? '',
            'subtitle' => $category->getTranslation('description', $locale),
            'image' => $category->featuredMedia?->toImageProps(),
            // Categories have no public archive route yet; link to the news index.
            'href' => Locale::path('news.index', [], $locale),
        ];
    }

    /**
     * @return array{title: string, subtitle: ?string, image: ?array<string, mixed>, href: ?string}|null
     */
    protected function resolvePageSlide(int $id, string $locale): ?array
    {
        $page = Page::query()->published()->with('heroMedia')->find($id);

        if ($page === null) {
            return null;
        }

        return [
            'title' => $page->getTranslation('title', $locale) ?? '',
            'subtitle' => $page->getTranslation('excerpt', $locale),
            'image' => $page->heroMedia?->toImageProps(),
            'href' => Locale::path('pages.show', ['slug' => $page->slug], $locale),
        ];
    }

    /**
     * hreflang alternates so search engines understand the four language
     * variants are the same document.
     *
     * @return list<array{locale: string, href: string}>
     */
    protected function alternates(Page $page): array
    {
        return array_map(fn (string $code) => [
            'locale' => Locale::htmlLang($code),
            'href' => $page->template === 'home'
                ? Locale::route('home', [], $code)
                : $page->urlFor($code),
        ], Locale::codes());
    }
}
