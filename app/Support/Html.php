<?php

declare(strict_types=1);

namespace App\Support;

use Stevebauman\Purify\Facades\Purify;

/**
 * HTML sanitisation for CMS rich-text content.
 *
 * Every string produced by the Tiptap editor passes through here before it is
 * stored, using the `content` HTMLPurifier profile (config/purify.php) — an
 * allowlist matching exactly what the editor can emit. Sanitising on write
 * means the public renderer can trust what it displays, and a compromised or
 * mistaken payload can never inject script.
 */
final class Html
{
    /**
     * Sanitise a single HTML string.
     */
    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        // Purify::clean() is polymorphic (array in → array out); a string in
        // always yields a string, but narrow it explicitly for the type system.
        $clean = Purify::config('content')->clean($html);

        return is_string($clean) ? $clean : '';
    }

    /**
     * Sanitise every value of a locale map of HTML.
     *
     * @param  array<string, string|null>  $map
     * @return array<string, string>
     */
    public static function cleanMap(array $map): array
    {
        $result = [];

        foreach ($map as $locale => $html) {
            if (! Locale::isSupported((string) $locale)) {
                continue;
            }

            $clean = self::clean($html);

            if ($clean !== '') {
                $result[$locale] = $clean;
            }
        }

        return $result;
    }

    /**
     * Plain-text preview of HTML — for excerpts, search and meta descriptions.
     */
    public static function toText(?string $html, int $limit = 0): string
    {
        $text = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5));
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return $limit > 0 ? mb_strimwidth($text, 0, $limit, '…') : $text;
    }
}
