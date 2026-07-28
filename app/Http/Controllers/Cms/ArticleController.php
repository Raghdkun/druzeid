<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Media;
use App\Support\Html;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $articles = Article::query()
            ->with('category')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->getTranslation('title', Locale::default()) ?: $article->slug,
                'status' => $article->status,
                'isFeatured' => $article->is_featured,
                'category' => $article->category?->getTranslation('name', Locale::default()),
                'publishedAt' => $article->published_at?->toDateString(),
            ]);

        return inertia('cms/articles/index', ['articles' => $articles]);
    }

    public function create(): Response
    {
        return $this->form(new Article(['status' => Article::STATUS_DRAFT]));
    }

    public function edit(Article $article): Response
    {
        return $this->form($article);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $article = new Article;
        $this->fill($article, $data);
        $article->author_id = $request->user()?->id;
        $article->save();

        return redirect()
            ->route('cms.articles.edit', $article->id)
            ->with('success', __('Article created.'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->fill($article, $this->validated($request, $article));
        $article->save();

        return back()->with('success', __('Article saved.'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()
            ->route('cms.articles.index')
            ->with('success', __('Article deleted.'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Article $article, array $data): void
    {
        $article->fill([
            'slug' => $data['slug'],
            'article_category_id' => $data['categoryId'] ?? null,
            'featured_media_id' => $data['featuredMediaId'] ?? null,
            'source_name' => $data['sourceName'] ?? null,
            'source_url' => $data['sourceUrl'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['publishedAt'] ?? null,
            'is_featured' => $data['isFeatured'] ?? false,
        ]);

        $article->setTranslations('title', $data['title']);
        $article->setTranslations('excerpt', $data['excerpt'] ?? []);
        // Body is rich text — sanitise every locale before storing.
        $article->setTranslations('body', Html::cleanMap($data['body'] ?? []));

        if ($article->status === Article::STATUS_PUBLISHED && $article->published_at === null) {
            $article->published_at = now();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'slug' => ['required', 'string', 'max:190', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'title' => ['required', 'array'],
            'title.*' => ['nullable', 'string', 'max:300'],
            'excerpt' => ['array'],
            'excerpt.*' => ['nullable', 'string', 'max:600'],
            'body' => ['array'],
            'body.*' => ['nullable', 'string'],
            'categoryId' => ['nullable', 'integer', 'exists:article_categories,id'],
            'featuredMediaId' => ['nullable', 'integer', 'exists:media,id'],
            'status' => ['required', Rule::in([Article::STATUS_DRAFT, Article::STATUS_PUBLISHED])],
            'publishedAt' => ['nullable', 'date'],
            'sourceName' => ['nullable', 'string', 'max:190'],
            'sourceUrl' => ['nullable', 'url', 'max:2048'],
            'isFeatured' => ['boolean'],
        ]);
    }

    private function form(Article $article): Response
    {
        return inertia('cms/articles/edit', [
            'article' => [
                'id' => $article->id,
                'slug' => $article->slug ?? '',
                'title' => $article->getTranslations('title'),
                'excerpt' => $article->getTranslations('excerpt'),
                'body' => $article->getTranslations('body'),
                'categoryId' => $article->article_category_id,
                'featuredMediaId' => $article->featured_media_id,
                'status' => $article->status ?? Article::STATUS_DRAFT,
                'publishedAt' => $article->published_at?->format('Y-m-d\TH:i'),
                'sourceName' => $article->source_name,
                'sourceUrl' => $article->source_url,
                'isFeatured' => (bool) $article->is_featured,
            ],
            'categories' => ArticleCategory::query()
                ->orderBy('sort')
                ->get()
                ->map(fn (ArticleCategory $c) => [
                    'id' => $c->id,
                    'name' => $c->getTranslation('name', Locale::default()) ?: $c->slug,
                ]),
            'mediaLibrary' => $this->mediaLibrary(),
            'locales' => $this->localeMeta(),
            'statuses' => [Article::STATUS_DRAFT, Article::STATUS_PUBLISHED],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mediaLibrary(): array
    {
        return array_values(
            Media::query()
                ->where('kind', 'image')
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
}
