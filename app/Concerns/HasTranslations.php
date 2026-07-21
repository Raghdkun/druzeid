<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * JSON-column translations.
 *
 * A translatable column stores `{"ar": "…", "en": "…"}`. Reading the attribute
 * resolves against the active locale with a fallback chain; writing a scalar
 * sets only the active locale, while writing an array replaces the whole set.
 *
 *   $page->title                          // resolved for app locale
 *   $page->getTranslation('title', 'en')  // specific locale
 *   $page->getTranslations('title')       // raw ['ar' => …, 'en' => …]
 *   $page->setTranslation('title', 'fr', '…')
 *
 * Chosen over a translations table because content volume is low and the CMS
 * edits every locale of a field together in one form.
 *
 * @phpstan-require-extends Model
 */
trait HasTranslations
{
    /**
     * Cast every translatable column to array so Eloquent handles the JSON.
     */
    public function initializeHasTranslations(): void
    {
        foreach ($this->getTranslatableAttributes() as $attribute) {
            $this->casts[$attribute] = 'array';
        }
    }

    /**
     * The translatable columns for this model. Models using the trait override
     * this to declare their JSON-translatable fields.
     *
     * @return list<string>
     */
    public function getTranslatableAttributes(): array
    {
        return [];
    }

    public function isTranslatable(string $key): bool
    {
        return in_array($key, $this->getTranslatableAttributes(), true);
    }

    /**
     * Raw translation map for a column, always an array.
     *
     * @return array<string, string|null>
     */
    public function getTranslations(string $key): array
    {
        $raw = parent::getAttributeValue($key);

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * Resolve a column for one locale, walking the fallback chain and then
     * any remaining locale, so a partially translated record still renders.
     */
    public function getTranslation(string $key, ?string $locale = null, bool $useFallback = true): ?string
    {
        $translations = $this->getTranslations($key);
        $locale ??= App::getLocale();

        $candidates = $useFallback ? Locale::chain($locale) : [$locale];

        foreach ($candidates as $candidate) {
            if (filled($translations[$candidate] ?? null)) {
                return $translations[$candidate];
            }
        }

        if ($useFallback) {
            foreach ($translations as $value) {
                if (filled($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * True only when this exact locale has content — no fallback.
     * Drives the CMS "translated / missing" indicator.
     */
    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        return filled($this->getTranslations($key)[$locale ?? App::getLocale()] ?? null);
    }

    public function setTranslation(string $key, string $locale, ?string $value): static
    {
        $translations = $this->getTranslations($key);

        if (blank($value)) {
            unset($translations[$locale]);
        } else {
            $translations[$locale] = $value;
        }

        parent::setAttribute($key, $translations);

        return $this;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function setTranslations(string $key, array $values): static
    {
        parent::setAttribute($key, $this->pruneTranslations($values));

        return $this;
    }

    /**
     * Fraction of translatable fields populated for a locale, 0.0–1.0.
     * Used by the CMS to show per-locale completeness at a glance.
     */
    public function translationCompleteness(string $locale): float
    {
        $attributes = $this->getTranslatableAttributes();

        if ($attributes === []) {
            return 1.0;
        }

        $filled = array_filter(
            $attributes,
            fn (string $attribute) => $this->hasTranslation($attribute, $locale)
        );

        return count($filled) / count($attributes);
    }

    /**
     * Match a translatable column within a single locale.
     *
     * @param  Builder<covariant Model>  $query
     * @return Builder<covariant Model>
     */
    public function scopeWhereTranslation(Builder $query, string $key, string $value, ?string $locale = null): Builder
    {
        return $query->where("{$key}->".($locale ?? App::getLocale()), $value);
    }

    // ---------------------------------------------------------------------
    // Eloquent overrides
    // ---------------------------------------------------------------------

    public function getAttributeValue($key)
    {
        if (! $this->isTranslatable($key)) {
            return parent::getAttributeValue($key);
        }

        return $this->getTranslation($key);
    }

    public function setAttribute($key, $value)
    {
        if (! $this->isTranslatable($key)) {
            return parent::setAttribute($key, $value);
        }

        // An array replaces the entire translation set (CMS save); a scalar
        // updates only the active locale (convenience in code and seeders).
        if (is_array($value)) {
            return parent::setAttribute($key, $this->pruneTranslations($value));
        }

        return $this->setTranslation($key, App::getLocale(), $value);
    }

    /**
     * Serialize translatables resolved for the active locale so Inertia props
     * carry display-ready strings. The CMS asks for raw maps explicitly.
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getTranslatableAttributes() as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = $this->getTranslation($key);
            }
        }

        return $attributes;
    }

    /**
     * Drop unsupported locales and empty values so the stored JSON stays
     * clean and `hasTranslation()` never sees an empty string as content.
     *
     * @param  array<string, string|null>  $values
     * @return array<string, string>
     */
    protected function pruneTranslations(array $values): array
    {
        return array_filter(
            $values,
            fn ($value, $locale) => Locale::isSupported((string) $locale) && filled($value),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
