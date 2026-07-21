<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Translate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $key
 * @property string $group
 * @property array<string, mixed>|null $value
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * Read a setting. Values are stored wrapped as {"value": …} so scalars,
     * arrays and locale maps all round-trip through one JSON column.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::map()[$key] ?? $default;
    }

    /**
     * A setting resolved for the active locale — for values stored as
     * locale maps, such as the site tagline.
     */
    public static function localized(string $key, ?string $locale = null): mixed
    {
        return Translate::resolve(self::get($key), $locale);
    }

    public static function put(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => ['value' => $value], 'group' => $group],
        );

        Cache::forget('settings.all');
    }

    /**
     * Every setting as a flat key => value map, cached because the site
     * chrome reads several on every request.
     *
     * Named `map()` rather than `all()` so it does not clash with Eloquent's
     * own static `all()`, which must keep returning a model collection.
     *
     * @return array<string, mixed>
     */
    public static function map(): array
    {
        return Cache::rememberForever('settings.all', fn () => static::query()
            ->get(['key', 'value'])
            ->mapWithKeys(fn (Setting $setting) => [
                $setting->key => $setting->value['value'] ?? null,
            ])
            ->all());
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }
}
