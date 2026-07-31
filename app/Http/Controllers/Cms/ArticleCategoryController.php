<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Models\Media;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * Category management (behind `can:manage-content`). Categories group news
 * articles and can carry an image used by dynamic hero slides.
 */
class ArticleCategoryController extends Controller
{
    public function index(): Response
    {
        return inertia('cms/categories/index', [
            'categories' => ArticleCategory::query()
                ->withCount('articles')
                ->orderBy('sort')
                ->get()
                ->map(fn (ArticleCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', Locale::default()) ?: $category->slug,
                    'slug' => $category->slug,
                    'accent' => $category->accent,
                    'articlesCount' => $category->articles_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return $this->form(new ArticleCategory);
    }

    public function edit(ArticleCategory $category): Response
    {
        return $this->form($category);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = new ArticleCategory;
        $this->fill($category, $this->validated($request));
        $category->save();

        return redirect()
            ->route('cms.categories.edit', $category->id)
            ->with('success', __('Category created.'));
    }

    public function update(Request $request, ArticleCategory $category): RedirectResponse
    {
        $this->fill($category, $this->validated($request, $category));
        $category->save();

        return back()->with('success', __('Category saved.'));
    }

    public function destroy(ArticleCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('cms.categories.index')
            ->with('success', __('Category deleted.'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(ArticleCategory $category, array $data): void
    {
        $category->fill([
            'slug' => $data['slug'],
            'accent' => $data['accent'] ?? null,
            'sort' => $data['sort'] ?? 0,
            'image_media_id' => $data['imageMediaId'] ?? null,
        ]);

        $category->setTranslations('name', $data['name']);
        $category->setTranslations('description', $data['description'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ArticleCategory $category = null): array
    {
        return $request->validate([
            'slug' => ['required', 'string', 'max:190', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique('article_categories', 'slug')->ignore($category?->id)],
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:190'],
            'description' => ['array'],
            'description.*' => ['nullable', 'string', 'max:600'],
            'accent' => ['nullable', Rule::in(['blue', 'green', 'red', 'yellow', 'white'])],
            'sort' => ['nullable', 'integer', 'min:0'],
            'imageMediaId' => ['nullable', 'integer', 'exists:media,id'],
        ]);
    }

    private function form(ArticleCategory $category): Response
    {
        return inertia('cms/categories/edit', [
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug ?? '',
                'name' => $category->getTranslations('name'),
                'description' => $category->getTranslations('description'),
                'accent' => $category->accent,
                'sort' => $category->sort ?? 0,
                'imageMediaId' => $category->image_media_id,
            ],
            'mediaLibrary' => $this->mediaLibrary(),
            'locales' => $this->localeMeta(),
            'accents' => ['blue', 'green', 'red', 'yellow', 'white'],
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
