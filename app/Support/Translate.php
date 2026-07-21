<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\App;

/**
 * Collapses locale maps anywhere inside a nested structure.
 *
 * Section payloads store every translatable string as `{"ar": "…", "en": "…"}`
 * at whatever depth it appears, including inside repeatable item arrays:
 *
 *   ['items' => [
 *       ['title' => ['ar' => 'أرشيف الدروز', 'en' => 'Druze Archives'], 'url' => '/x'],
 *   ]]
 *
 * `Translate::resolve()` walks that structure and replaces each locale map
 * with the string for the active locale, so React receives display-ready
 * props and never has to know translations exist.
 */
final class Translate
{
    /**
     * @param  mixed  $value
     * @return mixed
     */
    public static function resolve($value, ?string $locale = null)
    {
        $locale ??= App::getLocale();

        if (! is_array($value)) {
            return $value;
        }

        if (self::isLocaleMap($value)) {
            return self::pick($value, $locale);
        }

        return array_map(
            fn ($item) => self::resolve($item, $locale),
            $value
        );
    }

    /**
     * A locale map is a non-empty associative array whose keys are *all*
     * supported locale codes. An items list has integer keys and a config
     * object has keys like "columns", so neither is mistaken for one.
     *
     * @param  array<mixed>  $value
     */
    public static function isLocaleMap(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        foreach (array_keys($value) as $key) {
            if (! is_string($key) || ! Locale::isSupported($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve one locale map through the fallback chain, then any remaining
     * locale, so partially translated content still renders.
     *
     * @param  array<string, mixed>  $map
     */
    public static function pick(array $map, ?string $locale = null): ?string
    {
        foreach (Locale::chain($locale) as $candidate) {
            if (filled($map[$candidate] ?? null)) {
                return $map[$candidate];
            }
        }

        foreach ($map as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Build a locale map from a single value — convenience for seeders.
     *
     * @return array<string, string>
     */
    public static function map(?string $value, ?string $locale = null): array
    {
        if (blank($value)) {
            return [];
        }

        return [$locale ?? Locale::default() => $value];
    }
}
