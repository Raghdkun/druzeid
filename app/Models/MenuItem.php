<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Support\Locale;
use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property int|null $page_id
 * @property string|null $url
 * @property bool $opens_new_tab
 * @property int $sort
 * @property-read Page|null $page
 * @property-read Collection<int, MenuItem> $children
 */
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    use HasTranslations;

    protected $guarded = ['id'];

    /**
     * @return list<string>
     */
    public function getTranslatableAttributes(): array
    {
        return ['label'];
    }

    protected function casts(): array
    {
        return [
            'opens_new_tab' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('sort')
            ->with('page:id,slug');
    }

    /**
     * Resolved href, or null when this item is a dropdown parent that only
     * groups its children.
     */
    public function resolveUrl(?string $locale = null): ?string
    {
        if ($this->page !== null) {
            // Relative so Inertia always intercepts it (see Locale::path).
            return Locale::path('pages.show', ['slug' => $this->page->slug], $locale);
        }

        return $this->url;
    }

    /**
     * Shape consumed by the front-end navigation components.
     *
     * @return array<string, mixed>
     */
    public function toNavItem(?string $locale = null): array
    {
        return [
            'id' => $this->id,
            'label' => $this->getTranslation('label', $locale) ?? '',
            'href' => $this->resolveUrl($locale),
            'newTab' => $this->opens_new_tab,
            'children' => $this->relationLoaded('children')
                ? $this->children->map(fn (MenuItem $child) => $child->toNavItem($locale))->all()
                : [],
        ];
    }
}
