<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Media;
use App\Models\Page;
use App\Support\Locale;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * XML sitemap and robots.txt.
 *
 * Every published page is emitted once per locale, each `<url>` carrying:
 *  - `xhtml:link` alternates for all four locales plus an `x-default` pointing
 *    at the Arabic (default) version, so search engines map the language set
 *    correctly and pick the right variant per user;
 *  - `lastmod`, and a `changefreq`/`priority` hint tuned by template;
 *  - `image:image` entries for the page's hero and section media (video uses
 *    its poster frame), so the institution's photography surfaces in image
 *    search too.
 *
 * The rendered document is cached and invalidated by `SiteChrome::flush()`
 * whenever a page, menu or setting changes.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addDay(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $sitemap = url('/sitemap.xml');

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /cms',
            'Disallow: /login',
            'Disallow: /settings',
            '',
            "Sitemap: {$sitemap}",
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }

    private function build(): string
    {
        $pages = Page::query()
            ->published()
            ->with('sections:id,page_id,payload')
            ->orderBy('sort')
            ->get();

        $urls = [];

        foreach ($pages as $page) {
            $images = $this->images($page);

            foreach (Locale::codes() as $code) {
                $urls[] = [
                    'loc' => $this->locFor($page, $code),
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'changefreq' => $page->template === 'home' ? 'weekly' : 'monthly',
                    'priority' => $page->template === 'home' ? '1.0' : '0.7',
                    'alternates' => $this->alternates($page),
                    'images' => $images,
                ];
            }
        }

        $this->appendNews($urls);

        return view('sitemap', ['urls' => $urls])->render();
    }

    /**
     * Add the news index and every published article, once per locale.
     *
     * @param  list<array<string, mixed>>  $urls
     */
    private function appendNews(array &$urls): void
    {
        foreach (Locale::codes() as $code) {
            $urls[] = [
                'loc' => url(Locale::path('news.index', [], $code)),
                'lastmod' => null,
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'alternates' => array_map(fn (string $c) => [
                    'hreflang' => Locale::htmlLang($c),
                    'href' => url(Locale::path('news.index', [], $c)),
                ], Locale::codes()),
                'images' => [],
            ];
        }

        $articles = Article::query()->published()->with('featuredMedia')->get();

        foreach ($articles as $article) {
            $image = $article->featuredMedia;
            $images = $image !== null
                ? [['loc' => url(Storage::disk($image->disk)->url($image->path)), 'title' => null]]
                : [];

            foreach (Locale::codes() as $code) {
                $urls[] = [
                    'loc' => url($article->urlFor($code)),
                    'lastmod' => $article->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                    'alternates' => array_map(fn (string $c) => [
                        'hreflang' => Locale::htmlLang($c),
                        'href' => url($article->urlFor($c)),
                    ], Locale::codes()),
                    'images' => $images,
                ];
            }
        }
    }

    private function locFor(Page $page, string $code): string
    {
        return $page->template === 'home'
            ? Locale::route('home', [], $code)
            : $page->urlFor($code);
    }

    /**
     * @return list<array{hreflang: string, href: string}>
     */
    protected function alternates(Page $page): array
    {
        $alternates = array_map(fn (string $code) => [
            'hreflang' => Locale::htmlLang($code),
            'href' => $this->locFor($page, $code),
        ], Locale::codes());

        // x-default resolves to the Arabic (default) variant for unmatched users.
        $alternates[] = [
            'hreflang' => 'x-default',
            'href' => $this->locFor($page, Locale::default()),
        ];

        return $alternates;
    }

    /**
     * Absolute image URLs (with a title) for the page's hero and section media.
     * Video is represented by its poster so it, too, appears in image search.
     *
     * @return list<array{loc: string, title: ?string}>
     */
    protected function images(Page $page): array
    {
        $ids = collect([$page->hero_media_id])
            ->concat($page->sections->flatMap->mediaIds())
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $images = [];

        foreach (Media::query()->whereIn('id', $ids)->get() as $medium) {
            $path = $medium->is_video ? $medium->poster_path : $medium->path;

            if ($path === null) {
                continue;
            }

            $title = $medium->getTranslation('alt', Locale::default())
                ?: ($medium->getTranslation('caption', Locale::default()) ?: null);

            $images[] = [
                'loc' => url(Storage::disk($medium->disk)->url($path)),
                'title' => $title !== null ? (string) $title : null,
            ];

            if (count($images) >= 25) {
                break;
            }
        }

        return $images;
    }
}
