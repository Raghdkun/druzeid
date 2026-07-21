<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    public const PRIMARY = 'primary';

    public const FOOTER_SECTIONS = 'footer_sections';

    public const FOOTER_ORGANIZATION = 'footer_organization';

    protected $guarded = ['id'];

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort');
    }

    /**
     * Top-level items with their children eager-loaded, ready for rendering.
     *
     * @return HasMany<MenuItem, $this>
     */
    public function rootItems(): HasMany
    {
        return $this->items()
            ->whereNull('parent_id')
            ->with(['children.page:id,slug', 'page:id,slug']);
    }
}
