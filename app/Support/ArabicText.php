<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Arabic text normalisation.
 *
 * The WordPress source stretched headings with tatweel (kashida, U+0640) for
 * decoration — "تــــــــــاريخ الدروز" rather than "تاريخ الدروز". That is a
 * presentational flourish baked into the content: it breaks search, screen
 * readers pronounce it oddly, and it cannot be restyled. Stripping it here
 * keeps the text semantic and lets typography do the decorating.
 */
final class ArabicText
{
    private const TATWEEL = "\u{0640}";

    /** Arabic diacritics (harakat) — U+064B–U+0652, U+0670. */
    private const HARAKAT = '/[\x{064B}-\x{0652}\x{0670}]/u';

    /**
     * Remove decorative letter-stretching without touching real characters.
     */
    public static function stripTatweel(string $text): string
    {
        return str_replace(self::TATWEEL, '', $text);
    }

    /**
     * Remove diacritics — for comparison keys only, never for display, since
     * harakat carry meaning in quoted and religious text.
     */
    public static function stripHarakat(string $text): string
    {
        return (string) preg_replace(self::HARAKAT, '', $text);
    }

    /**
     * Display-ready: tatweel removed, whitespace collapsed, stray decorative
     * underscore rules and dangling quote marks trimmed.
     */
    public static function clean(string $text): string
    {
        $text = self::stripTatweel($text);
        $text = (string) preg_replace('/_{2,}/u', '', $text);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text, " \t\n\r\0\x0B\"“”");
    }

    /**
     * Comparison key: tatweel and harakat removed, alef/ya/ta-marbuta folded,
     * lowercased. Used to match headings against known section names.
     */
    public static function key(string $text): string
    {
        $text = self::stripHarakat(self::stripTatweel($text));

        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي', 'ئ' => 'ي',
            'ة' => 'ه',
            'ؤ' => 'و',
        ]);

        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return trim(mb_strtolower($text));
    }

    /**
     * True when the line is a decorative section heading — the source marked
     * these with a trailing run of underscores.
     */
    public static function isUnderscoreHeading(string $line): bool
    {
        return (bool) preg_match('/_{3,}\s*$/u', $line);
    }

    /**
     * True when the line is a numbered or bulleted list item.
     * Matches "1- x", "1 – x", "A - x", "-x", "• x".
     */
    public static function isListItem(string $line): bool
    {
        return (bool) preg_match('/^\s*(?:[0-9]{1,2}|[A-Ea-e])\s*[-–—]\s*\S/u', $line)
            || (bool) preg_match('/^\s*[-–—•]\s*\S/u', $line);
    }

    /**
     * Strip the leading marker from a list item, keeping its text.
     */
    public static function stripListMarker(string $line): string
    {
        $line = (string) preg_replace('/^\s*(?:[0-9]{1,2}|[A-Ea-e])\s*[-–—]\s*/u', '', $line);
        $line = (string) preg_replace('/^\s*[-–—•]\s*/u', '', $line);

        return trim($line);
    }
}
