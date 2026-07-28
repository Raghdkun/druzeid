<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use Database\Factories\ArticleCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string|null $accent
 * @property int $sort
 */
class ArticleCategory extends Model
{
    /** @use HasFactory<ArticleCategoryFactory> */
    use HasFactory;

    use HasTranslations;

    protected $guarded = ['id'];

    /**
     * @return list<string>
     */
    public function getTranslatableAttributes(): array
    {
        return ['name', 'description'];
    }

    protected function casts(): array
    {
        return ['sort' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
