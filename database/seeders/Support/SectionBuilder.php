<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Support\ArabicText;
use App\Support\Locale;

/**
 * Turns parsed content blocks into page-section payloads.
 *
 * Prose is emitted as sanitised-ready HTML, one string per locale, so it slots
 * straight into the rich-text content model. The Arabic HTML is built from the
 * extracted blocks; each other locale is assembled from ContentDictionary and
 * included only when every string in the section has a translation — so a
 * section is either fully translated or falls back to Arabic, never mixed.
 */
final class SectionBuilder
{
    /**
     * Locale map for a single Arabic string (title, heading, label), with any
     * available translations.
     *
     * @return array<string, string>
     */
    public static function tr(?string $arabic): array
    {
        if ($arabic === null || $arabic === '') {
            return [];
        }

        return ContentDictionary::localeMap($arabic);
    }

    /**
     * Wrap each value of a translation array, dropping empties.
     *
     * @param  array<string, string|null>  $values
     * @return array<string, string>
     */
    public static function locales(array $values): array
    {
        return array_filter($values, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * `rich_text` payload: a locale map of HTML.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    public static function richText(array $blocks): array
    {
        return ['html' => self::htmlFromBlocks($blocks)];
    }

    /**
     * Assemble prose blocks into an HTML string per locale.
     *
     * Arabic is always produced. A translated locale is included only if every
     * block yields a translation for it, guaranteeing a section is never a
     * mix of languages.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, string>
     */
    public static function htmlFromBlocks(array $blocks): array
    {
        $result = [];

        foreach (self::localesToBuild() as $locale) {
            $html = '';
            $complete = true;

            foreach ($blocks as $block) {
                $piece = self::blockHtml($block, $locale);

                if ($piece === null) {
                    $complete = false;
                    break;
                }

                $html .= $piece;
            }

            if ($locale === Locale::default()) {
                $result[$locale] = $html;
            } elseif ($complete && $html !== '') {
                $result[$locale] = $html;
            }
        }

        return $result;
    }

    /**
     * HTML for one block in one locale, or null when a required translation is
     * missing (non-default locale only).
     *
     * @param  array<string, mixed>  $block
     */
    private static function blockHtml(array $block, string $locale): ?string
    {
        return match ($block['kind']) {
            'heading' => self::wrap('h3', self::text($block['text'], $locale)),
            'paragraph' => self::wrap('p', self::text($block['text'], $locale)),
            'list' => self::listHtml($block['items'], $locale),
            'definitions' => self::definitionsHtml($block['items'], $locale),
            default => '',
        };
    }

    /**
     * Resolve a string for a locale: Arabic verbatim, others via the
     * dictionary. Returns null when a non-default translation is missing.
     */
    private static function text(string $arabic, string $locale): ?string
    {
        if ($locale === Locale::default()) {
            return $arabic;
        }

        return ContentDictionary::translations($arabic)[$locale] ?? null;
    }

    private static function wrap(string $tag, ?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return "<{$tag}>".e($text)."</{$tag}>";
    }

    /**
     * @param  list<string>  $items
     */
    private static function listHtml(array $items, string $locale): ?string
    {
        $out = '';

        foreach ($items as $item) {
            $text = self::text($item, $locale);
            if ($text === null) {
                return null;
            }
            $out .= '<li>'.e($text).'</li>';
        }

        return $out === '' ? '' : "<ul>{$out}</ul>";
    }

    /**
     * Definition pairs render as a heading + paragraph each.
     *
     * @param  list<array{term: string, description: string}>  $items
     */
    private static function definitionsHtml(array $items, string $locale): ?string
    {
        $out = '';

        foreach ($items as $pair) {
            $term = self::text($pair['term'], $locale);
            $desc = self::text($pair['description'], $locale);
            if ($term === null || $desc === null) {
                return null;
            }
            $out .= '<h4>'.e($term).'</h4><p>'.e($desc).'</p>';
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function localesToBuild(): array
    {
        return Locale::codes();
    }

    /**
     * Split blocks at the first heading: everything before it is the intro,
     * everything after is grouped under its heading.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return array{intro: list<array<string, mixed>>, groups: list<array<string, mixed>>}
     */
    public static function splitAtHeadings(array $blocks): array
    {
        $intro = [];
        $groups = [];
        $current = null;

        foreach ($blocks as $block) {
            if ($block['kind'] === 'heading') {
                if ($current !== null) {
                    $groups[] = $current;
                }

                $current = ['title' => $block['text'], 'blocks' => []];

                continue;
            }

            if ($current === null) {
                $intro[] = $block;

                continue;
            }

            $current['blocks'][] = $block;
        }

        if ($current !== null) {
            $groups[] = $current;
        }

        return ['intro' => $intro, 'groups' => $groups];
    }

    /**
     * Accordion payload from heading-led groups — each item a translated title
     * plus a locale map of HTML.
     *
     * @param  list<array<string, mixed>>  $groups
     * @return array<string, mixed>
     */
    public static function accordion(array $groups, ?string $heading = null): array
    {
        return [
            'heading' => self::tr($heading),
            'items' => array_map(fn (array $group) => [
                'title' => self::tr($group['title']),
                'html' => self::htmlFromBlocks($group['blocks']),
            ], $groups),
        ];
    }

    /**
     * Drop the leading line when it merely repeats the page title.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public static function dropRedundantLead(array $blocks, string $title): array
    {
        if ($blocks === [] || $blocks[0]['kind'] !== 'paragraph') {
            return $blocks;
        }

        $lead = ArabicText::key($blocks[0]['text']);
        $titleKey = ArabicText::key($title);

        if ($lead === $titleKey || str_contains($lead, $titleKey)) {
            if (mb_strlen($blocks[0]['text']) <= mb_strlen($title) + 12) {
                return array_slice($blocks, 1);
            }
        }

        return $blocks;
    }
}
