<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
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
                    ->map(fn (PageSection $section) => [
                        'id' => $section->id,
                        'type' => $section->type,
                        ...$this->resolveSlugHrefs($section->resolvedPayload($locale), $locale),
                    ])
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
