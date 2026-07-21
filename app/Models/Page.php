<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Support\Locale;
use Carbon\CarbonImmutable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $template
 * @property string|null $accent
 * @property int|null $hero_media_id
 * @property string $status
 * @property CarbonImmutable|null $published_at
 * @property int $sort
 * @property int|null $sections_count
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
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
        return ['title', 'excerpt', 'seo_title', 'seo_description'];
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<PageSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function heroMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id');
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * Canonical URL for this page in the given (or active) locale.
     */
    public function urlFor(?string $locale = null): string
    {
        return Locale::route('pages.show', ['slug' => $this->slug], $locale);
    }
}
