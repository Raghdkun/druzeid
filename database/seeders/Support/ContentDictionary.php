<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Support\ArabicText;

/**
 * Translations of the site's Arabic source content into English, French and
 * Dutch, keyed by the Arabic string itself.
 *
 * The original site was authored in Arabic; every other language is a
 * translation of it. Keying by the source string means the seeder can look up
 * a translation for any extracted paragraph, heading, list item, title or
 * card without threading identifiers through the extraction pipeline — and a
 * missing entry simply falls back to Arabic rather than rendering blank.
 *
 * Lookups normalise the key (tatweel/harakat stripped, alef/ya/ta folded) so
 * incidental differences in the source don't cause misses.
 */
final class ContentDictionary
{
    /**
     * @return array<string, string|null> locale => translation (null = none)
     */
    public static function translations(string $arabic): array
    {
        $entry = self::map()[ArabicText::key($arabic)] ?? [];

        return [
            'en' => $entry['en'] ?? null,
            'fr' => $entry['fr'] ?? null,
            'nl' => $entry['nl'] ?? null,
        ];
    }

    /**
     * A full locale map for a single Arabic string: always `ar`, plus any
     * available translations.
     *
     * @return array<string, string>
     */
    public static function localeMap(string $arabic): array
    {
        $map = ['ar' => $arabic];

        foreach (self::translations($arabic) as $locale => $value) {
            if (filled($value)) {
                $map[$locale] = $value;
            }
        }

        return $map;
    }

    /**
     * A handful of labels the seeder introduces itself (they are not part of
     * the extracted source, so they are not in ContentTranslations).
     *
     * @var array<string, array{en: string, fr: string, nl: string}>
     */
    private const SUPPLEMENT = [
        'الأقسام المخطط لها' => [
            'en' => 'Planned sections',
            'fr' => 'Sections prévues',
            'nl' => 'Geplande secties',
        ],
        'أقســام الموقع' => [
            'en' => 'Site sections',
            'fr' => 'Sections du site',
            'nl' => 'Sitesecties',
        ],
    ];

    /**
     * The translation table, keyed by the normalised Arabic source: the
     * generated ContentTranslations plus the seeder's own labels.
     *
     * @return array<string, array{en?: string, fr?: string, nl?: string}>
     */
    private static function map(): array
    {
        static $normalised = null;

        if ($normalised === null) {
            $normalised = [];

            // The translation table is generated separately; until it exists,
            // every locale simply falls back to Arabic.
            $source = class_exists(ContentTranslations::class)
                ? ContentTranslations::all() + self::SUPPLEMENT
                : self::SUPPLEMENT;

            foreach ($source as $arabic => $translations) {
                $normalised[ArabicText::key($arabic)] = $translations;
            }
        }

        return $normalised;
    }
}
