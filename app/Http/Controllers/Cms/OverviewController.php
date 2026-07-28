<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Media;
use App\Models\Page;
use App\Support\Locale;
use Inertia\Response;

class OverviewController extends Controller
{
    public function index(): Response
    {
        return inertia('cms/overview', [
            'stats' => [
                'pages' => Page::count(),
                'published' => Page::published()->count(),
                'drafts' => Page::where('status', Page::STATUS_DRAFT)->count(),
                'media' => Media::count(),
                'altReviewQueue' => Media::where('needs_alt_review', true)->count(),
                'articles' => Article::count(),
                'articlesPublished' => Article::where('status', Article::STATUS_PUBLISHED)->count(),
            ],
            'translationCoverage' => $this->translationCoverage(),
            'recentPages' => Page::query()
                ->latest('updated_at')
                ->take(6)
                ->get()
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->getTranslation('title', Locale::default()),
                    'status' => $page->status,
                    'updatedAt' => $page->updated_at?->toIso8601String(),
                    'completeness' => $this->pageCompleteness($page),
                ]),
        ]);
    }

    /**
     * Site-wide translation completeness per locale — the share of page
     * titles that have content in each language. Surfaces at a glance how
     * much of the fr/nl (and en) content still needs filling in.
     *
     * @return list<array{locale: string, name: string, percent: int}>
     */
    protected function translationCoverage(): array
    {
        $pages = Page::all();
        $total = max($pages->count(), 1);

        return array_map(function (string $code) use ($pages, $total) {
            $filled = $pages->filter(fn (Page $page) => $page->hasTranslation('title', $code))->count();

            return [
                'locale' => $code,
                'name' => Locale::meta($code)['name'],
                'percent' => (int) round($filled / $total * 100),
            ];
        }, Locale::codes());
    }

    /**
     * @return array<string, int>
     */
    protected function pageCompleteness(Page $page): array
    {
        $result = [];

        foreach (Locale::codes() as $code) {
            $result[$code] = (int) round($page->translationCompleteness($code) * 100);
        }

        return $result;
    }
}
