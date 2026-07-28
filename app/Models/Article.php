<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Support\Locale;
use Carbon\CarbonImmutable;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * أخبار الدروز — a news article. Body is a per-locale HTML map (sanitised on
 * save like page sections); title/excerpt are translatable strings.
 *
 * @property int $id
 * @property string $slug
 * @property int|null $article_category_id
 * @property int|null $featured_media_id
 * @property int|null $author_id
 * @property string|null $source_name
 * @property string|null $source_url
 * @property string $status
 * @property CarbonImmutable|null $published_at
 * @property bool $is_featured
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use HasTranslations;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $guarded = ['id'];

    /**
     * @return list<string>
     */
    public function getTranslatableAttributes(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<ArticleCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Published and past its publish time (or with none set). Latest first.
     *
     * @param  Builder<Article>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * Localised URL for this article in the active (or given) locale — relative
     * so Inertia always intercepts it (matches Locale::path usage site-wide).
     */
    public function urlFor(?string $locale = null): string
    {
        return Locale::path('news.show', ['slug' => $this->slug], $locale);
    }

    /**
     * Compact shape for cards, the homepage slider and the news index.
     *
     * @return array<string, mixed>
     */
    public function toCardProps(?string $locale = null): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'href' => $this->urlFor($locale),
            'title' => $this->getTranslation('title', $locale) ?? '',
            'excerpt' => $this->getTranslation('excerpt', $locale),
            'image' => $this->featuredMedia?->toImageProps(),
            'publishedAt' => $this->published_at?->toIso8601String(),
            'category' => $this->category?->getTranslation('name', $locale),
        ];
    }
}
