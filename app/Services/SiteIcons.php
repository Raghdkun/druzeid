<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Derives the site's icon set (favicon, Apple touch icon, PWA icons) from one
 * square source image chosen in the CMS.
 *
 * Browsers want several fixed pixel sizes rather than a responsive srcset, so
 * these are generated once when the icon is chosen and their paths stored on
 * the `site.icon_paths` setting. Everything is cropped to a square via cover()
 * so a slightly off-square upload still yields a correct icon, and encoded as
 * PNG because that is the format every target (favicon, apple-touch-icon, web
 * manifest) accepts and it preserves transparency.
 */
class SiteIcons
{
    /** Pixel sizes browsers ask for: favicon, apple-touch, PWA small/large. */
    public const SIZES = [32, 180, 192, 512];

    private const DIRECTORY = 'icons';

    public function __construct(private readonly ImageManager $manager) {}

    /**
     * Build the icon set for a media item. Returns a size => path map, or an
     * empty array when the source is unusable (video, missing file, decode
     * failure) so the caller falls back to the bundled default icon.
     *
     * @return array<int, string>
     */
    public function generate(Media $media, string $disk = 'public'): array
    {
        $storage = Storage::disk($disk);

        if ($media->is_video || ! $storage->exists($media->path)) {
            return [];
        }

        $paths = [];

        foreach (self::SIZES as $size) {
            $path = self::DIRECTORY."/icon-{$size}.png";

            try {
                $image = $this->manager->read($storage->get($media->path));
                // cover() crops to fill, so the icon is always exactly square.
                $image->cover($size, $size);
                $storage->put($path, (string) $image->toPng());
                $paths[$size] = $path;
            } catch (Throwable) {
                // Skip a size that fails to encode rather than losing the set.
            }
        }

        return $paths;
    }

    /**
     * Remove a previously generated icon set.
     *
     * @param  array<int|string, string>  $paths
     */
    public function delete(array $paths, string $disk = 'public'): void
    {
        $values = array_values($paths);

        if ($values !== []) {
            Storage::disk($disk)->delete($values);
        }
    }
}
