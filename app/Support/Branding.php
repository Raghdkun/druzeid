<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Media;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves the site's visual identity — icons, theme colour, share image and
 * display name — from CMS settings, falling back to the bundled defaults so a
 * fresh install still renders a complete <head>.
 *
 * Reads go through Setting::map(), which is already cached, so this is cheap
 * enough to call directly from Blade.
 */
final class Branding
{
    /** Shipped with the repo; used until an icon is uploaded in the CMS. */
    public const FALLBACK_ICON = '/favicon.svg';

    public const DEFAULT_THEME_COLOR = '#0057B8';

    public const DEFAULT_BACKGROUND_COLOR = '#FFFFFF';

    /**
     * Generated icon URLs keyed by pixel size, or an empty array when no icon
     * has been set.
     *
     * @return array<int, string>
     */
    public static function icons(): array
    {
        $paths = Setting::get('site.icon_paths', []);

        if (! is_array($paths) || $paths === []) {
            return [];
        }

        $disk = Storage::disk('public');
        $icons = [];

        foreach ($paths as $size => $path) {
            if (is_string($path) && $disk->exists($path)) {
                $icons[(int) $size] = $disk->url($path);
            }
        }

        return $icons;
    }

    /**
     * The icon URL at a given size, falling back to the bundled default.
     */
    public static function icon(int $size): string
    {
        return self::icons()[$size] ?? self::FALLBACK_ICON;
    }

    public static function hasCustomIcon(): bool
    {
        return self::icons() !== [];
    }

    public static function themeColor(): string
    {
        $color = Setting::get('site.theme_color');

        return is_string($color) && $color !== '' ? $color : self::DEFAULT_THEME_COLOR;
    }

    public static function backgroundColor(): string
    {
        return self::DEFAULT_BACKGROUND_COLOR;
    }

    /**
     * Absolute URL of the default social share image, used when a page has no
     * hero image of its own.
     */
    public static function ogImageUrl(): ?string
    {
        return self::mediaUrl(Setting::get('site.og_image_media_id'));
    }

    /**
     * Optional wordmark/logo shown in the site header in place of the emblem.
     */
    public static function logoUrl(): ?string
    {
        return self::mediaUrl(Setting::get('site.logo_media_id'));
    }

    /**
     * The site's display name in the active (or given) locale.
     */
    public static function appName(?string $locale = null): string
    {
        $name = Setting::localized('site.short_name', $locale);

        return is_string($name) && $name !== '' ? $name : (string) config('app.name');
    }

    /**
     * Default meta description used when a page defines none.
     */
    public static function description(?string $locale = null): ?string
    {
        $description = Setting::localized('site.seo_description', $locale);

        return is_string($description) && $description !== '' ? $description : null;
    }

    private static function mediaUrl(mixed $mediaId): ?string
    {
        if (! is_int($mediaId) && ! (is_string($mediaId) && ctype_digit($mediaId))) {
            return null;
        }

        $media = Media::find((int) $mediaId);

        if ($media === null) {
            return null;
        }

        // Prefer the poster for video so the share image is always an image.
        $path = $media->is_video ? $media->poster_path : $media->path;

        return $path === null ? null : url(Storage::disk($media->disk)->url($path));
    }
}
