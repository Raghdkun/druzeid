<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use App\Services\SiteChrome;
use App\Support\Locale;
use Inertia\Response;

/**
 * أخبار الدروز — the public news index and article pages.
 */
class NewsController extends Controller
{
    public function __construct(private readonly SiteChrome $chrome) {}

    public function index(): Response
    {
        $locale = app()->getLocale();

        $articles = Article::query()
            ->published()
            ->with(['featuredMedia', 'category'])
            ->paginate(12)
            ->through(fn (Article $article) => $article->toCardProps($locale));

        return inertia('site/news-index', [
            ...$this->chrome->props($locale),
            'articles' => $articles,
            'meta' => [
                'title' => __('nav.news'),
                'description' => Setting::localized('site.seo_description', $locale),
                'canonical' => url(Locale::path('news.index', [], $locale)),
                'alternates' => $this->indexAlternates(),
                'ogImage' => null,
                'ogLocale' => str_replace('-', '_', Locale::htmlLang($locale)),
                'siteName' => Setting::localized('site.short_name', $locale) ?? config('app.name'),
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $locale = app()->getLocale();

        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->with(['featuredMedia', 'category', 'author'])
            ->firstOrFail();

        $image = $article->featuredMedia?->toImageProps();

        return inertia('site/article', [
            ...$this->chrome->props($locale),
            'article' => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->getTranslation('title', $locale) ?? '',
                'excerpt' => $article->getTranslation('excerpt', $locale),
                'body' => $article->getTranslation('body', $locale) ?? '',
                'image' => $image,
                'category' => $article->category?->getTranslation('name', $locale),
                'publishedAt' => $article->published_at?->toIso8601String(),
                // CarbonImmutable locale is already set by SetLocale for the
                // active request, so isoFormat renders in the right language.
                'publishedLabel' => $article->published_at?->isoFormat('LL'),
                'sourceName' => $article->source_name,
                'sourceUrl' => $article->source_url,
            ],
            'meta' => [
                'title' => $article->getTranslation('title', $locale),
                'description' => $article->getTranslation('excerpt', $locale),
                'canonical' => url($article->urlFor($locale)),
                'alternates' => $this->showAlternates($article),
                'ogImage' => $image['url'] ?? null,
                'ogLocale' => str_replace('-', '_', Locale::htmlLang($locale)),
                'siteName' => Setting::localized('site.short_name', $locale) ?? config('app.name'),
            ],
        ]);
    }

    /**
     * @return list<array{locale: string, href: string}>
     */
    private function indexAlternates(): array
    {
        return array_map(fn (string $code) => [
            'locale' => Locale::htmlLang($code),
            'href' => url(Locale::path('news.index', [], $code)),
        ], Locale::codes());
    }

    /**
     * @return list<array{locale: string, href: string}>
     */
    private function showAlternates(Article $article): array
    {
        return array_map(fn (string $code) => [
            'locale' => Locale::htmlLang($code),
            'href' => url($article->urlFor($code)),
        ], Locale::codes());
    }
}
