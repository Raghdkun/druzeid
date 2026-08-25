<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\App;

/**
 * Site-wide theme: brand colours and per-language typography, edited in the CMS
 * and injected as CSS custom properties into the document `<head>` so they
 * override the static tokens in app.css. Nothing here is emitted unless an
 * admin has actually customised it — an untouched site renders the shipped
 * palette and fonts.
 *
 * A single accent/neutral drives several tokens (e.g. the blue also sets
 * `--primary`, `--ring`, charts, and its own derived deep/tint shades), so this
 * is the one place that mapping lives.
 */
final class Theme
{
    /** Curated, self-hosted font families (bundled at build time in vite.config.ts). */
    public const FONTS = [
        'cairo' => ['label' => 'Cairo', 'family' => 'Cairo', 'serif' => false],
        'ibm-plex-sans-arabic' => ['label' => 'IBM Plex Sans Arabic', 'family' => 'IBM Plex Sans Arabic', 'serif' => false],
        'tajawal' => ['label' => 'Tajawal', 'family' => 'Tajawal', 'serif' => false],
        'almarai' => ['label' => 'Almarai', 'family' => 'Almarai', 'serif' => false],
        'amiri' => ['label' => 'Amiri', 'family' => 'Amiri', 'serif' => true],
        'rubik' => ['label' => 'Rubik', 'family' => 'Rubik', 'serif' => false],
        'inter' => ['label' => 'Inter', 'family' => 'Inter', 'serif' => false],
        'lora' => ['label' => 'Lora', 'family' => 'Lora', 'serif' => true],
    ];

    public const DEFAULT_DISPLAY = 'cairo';

    public const DEFAULT_BODY = 'ibm-plex-sans-arabic';

    /** Theme colour setting keys → validated hex, else null. */
    public const COLOR_KEYS = ['blue', 'green', 'red', 'yellow', 'ink', 'surface', 'bar_dark'];

    /** The shipped palette (matches resources/css/app.css). A saved value equal
     *  to its default is stored as null so no redundant override is emitted. */
    public const DEFAULTS = [
        'blue' => '#069ed9',
        'green' => '#00843d',
        'red' => '#c8102e',
        'yellow' => '#ffb81c',
        'ink' => '#1f1f1f',
        'surface' => '#ffffff',
        'bar_dark' => '#262626',
    ];

    /**
     * The CSS custom-property overrides for the current theme, as a single
     * `:root` body string. Empty when nothing is customised (so the caller can
     * skip the `<style>` entirely).
     */
    public static function cssVariables(?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $lines = self::colorDeclarations();

        $display = Setting::get("theme.font.{$locale}.display");
        if (is_string($display) && $display !== self::DEFAULT_DISPLAY && isset(self::FONTS[$display])) {
            $lines[] = '--font-display: '.self::fontStack($display).';';
        }

        $body = Setting::get("theme.font.{$locale}.body");
        if (is_string($body) && $body !== self::DEFAULT_BODY && isset(self::FONTS[$body])) {
            $lines[] = '--font-sans: '.self::fontStack($body).';';
        }

        return implode(' ', $lines);
    }

    /**
     * The stored theme values shaped for the CMS editor (nulls where unset).
     *
     * @return array{colors: array<string, string|null>, fonts: array<string, array{display: string, body: string}>}
     */
    public static function forEditor(): array
    {
        $colors = [];
        foreach (self::COLOR_KEYS as $key) {
            $colors[$key] = self::color($key);
        }

        $fonts = [];
        foreach (Locale::codes() as $code) {
            $fonts[$code] = [
                'display' => self::fontKey("theme.font.{$code}.display", self::DEFAULT_DISPLAY),
                'body' => self::fontKey("theme.font.{$code}.body", self::DEFAULT_BODY),
            ];
        }

        return ['colors' => $colors, 'fonts' => $fonts];
    }

    /**
     * @return list<string>
     */
    private static function colorDeclarations(): array
    {
        $lines = [];

        if ($blue = self::color('blue')) {
            $lines[] = "--color-druze-blue: {$blue};";
            $lines[] = "--primary: {$blue};";
            $lines[] = "--ring: {$blue};";
            $lines[] = "--chart-1: {$blue};";
            $lines[] = "--sidebar-primary: {$blue};";
            $lines[] = "--sidebar-ring: {$blue};";
            $lines[] = '--color-druze-blue-deep: '.self::adjust($blue, -0.12).';';
            $lines[] = '--color-druze-blue-tint: '.self::withLightness($blue, 0.93).';';
        }

        if ($green = self::color('green')) {
            $lines[] = "--color-druze-green: {$green};";
        }

        if ($red = self::color('red')) {
            $lines[] = "--color-druze-red: {$red};";
        }

        if ($yellow = self::color('yellow')) {
            $lines[] = "--color-druze-yellow: {$yellow};";
        }

        if ($ink = self::color('ink')) {
            $lines[] = "--color-ink: {$ink};";
            $lines[] = "--foreground: {$ink};";
            $lines[] = '--color-ink-soft: '.self::adjust($ink, 0.08).';';
            $lines[] = '--color-ink-eyebrow: '.self::adjust($ink, 0.18).';';
            $lines[] = '--color-ink-muted: '.self::adjust($ink, 0.25).';';
        }

        if ($surface = self::color('surface')) {
            $lines[] = "--color-surface: {$surface};";
            $lines[] = "--background: {$surface};";
            $lines[] = "--card: {$surface};";
            $lines[] = "--popover: {$surface};";
            $lines[] = '--color-surface-alt: '.self::adjust($surface, -0.04).';';
            $lines[] = '--color-surface-sunken: '.self::adjust($surface, -0.08).';';
            $lines[] = '--color-hairline: '.self::adjust($surface, -0.12).';';
        }

        if ($bar = self::color('bar_dark')) {
            $lines[] = "--color-bar-dark: {$bar};";
        }

        return $lines;
    }

    /** A stored theme colour if it is a valid #rrggbb, else null. */
    private static function color(string $key): ?string
    {
        $value = Setting::get("theme.color.{$key}");

        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : null;
    }

    private static function fontKey(string $settingKey, string $default): string
    {
        $value = Setting::get($settingKey);

        return is_string($value) && isset(self::FONTS[$value]) ? $value : $default;
    }

    private static function fontStack(string $key): string
    {
        $font = self::FONTS[$key];
        // An Arabic-capable fallback keeps Arabic glyphs rendering even when a
        // Latin-only family is chosen.
        $fallback = $font['serif'] ? "'Amiri', Georgia, serif" : "'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif";
        $primary = "'{$font['family']}'";

        // The chosen family may already lead the fallback (e.g. Amiri chosen and
        // Amiri as the serif fallback) — don't list it twice.
        return str_starts_with($fallback, $primary.',') ? $fallback : "{$primary}, {$fallback}";
    }

    // -- colour maths (hex ⇄ HSL, lighten/darken) ---------------------------

    private static function adjust(string $hex, float $delta): string
    {
        [$h, $s, $l] = self::toHsl($hex);

        return self::toHex($h, $s, max(0.0, min(1.0, $l + $delta)));
    }

    private static function withLightness(string $hex, float $lightness): string
    {
        [$h, $s] = self::toHsl($hex);

        return self::toHex($h, $s, max(0.0, min(1.0, $lightness)));
    }

    /**
     * @return array{float, float, float}
     */
    private static function toHsl(string $hex): array
    {
        $r = hexdec(substr($hex, 1, 2)) / 255;
        $g = hexdec(substr($hex, 3, 2)) / 255;
        $b = hexdec(substr($hex, 5, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d <= 0.0) {
            return [0.0, 0.0, $l];
        }

        $denominator = $l > 0.5 ? (2.0 - $max - $min) : ($max + $min);
        $s = $denominator <= 0.0 ? 0.0 : $d / $denominator;

        $h = match ($max) {
            $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [$h / 6, $s, $l];
    }

    private static function toHex(float $h, float $s, float $l): string
    {
        if ($s === 0.0) {
            $v = (int) round($l * 255);

            return sprintf('#%02x%02x%02x', $v, $v, $v);
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        $r = self::hue($p, $q, $h + 1 / 3);
        $g = self::hue($p, $q, $h);
        $b = self::hue($p, $q, $h - 1 / 3);

        return sprintf('#%02x%02x%02x', (int) round($r * 255), (int) round($g * 255), (int) round($b * 255));
    }

    private static function hue(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }
}
