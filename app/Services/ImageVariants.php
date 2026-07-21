<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Generates responsive next-gen derivatives (WebP, and AVIF where the image
 * driver supports it) for a stored raster image.
 *
 * Derivatives are downscale-only — a width larger than the source is skipped,
 * so nothing is upscaled — and vector/SVG sources are left untouched. The
 * returned map is stored on the media row and drives the <picture> srcset on
 * the public site, cutting image weight substantially over the source JPEGs.
 */
class ImageVariants
{
    /** Responsive target widths, in CSS pixels. */
    private const WIDTHS = [480, 768, 1200, 1920];

    private const WEBP_QUALITY = 78;

    private const AVIF_QUALITY = 55;

    public function __construct(private readonly ImageManager $manager) {}

    /**
     * Build derivatives for a stored image and return the variant map.
     *
     * @return array<string, array<int, string>> format => [width => path]
     */
    public function generate(string $path, string $disk = 'public'): array
    {
        $storage = Storage::disk($disk);

        // Vectors need no raster derivatives; videos are not images and must
        // never be read into the image decoder.
        if (! $storage->exists($path) || $this->isVector($path) || $this->isVideo($path)) {
            return [];
        }

        try {
            $source = $this->manager->read($storage->get($path));
        } catch (Throwable) {
            return [];
        }

        $sourceWidth = $source->width();
        $base = $this->basePath($path);
        $variants = [];

        foreach ($this->formats() as $format => $encoder) {
            foreach (self::WIDTHS as $width) {
                // Never upscale; always include the smallest so tiny sources
                // still get one derivative.
                if ($width > $sourceWidth && $width !== self::WIDTHS[0]) {
                    continue;
                }

                $target = min($width, $sourceWidth);
                $variantPath = "{$base}-{$target}.{$format}";

                try {
                    $image = $this->manager->read($storage->get($path));
                    $image->scaleDown(width: $target);
                    $storage->put($variantPath, (string) $encoder($image));
                    $variants[$format][$target] = $variantPath;
                } catch (Throwable) {
                    // Skip a width/format that fails to encode (e.g. AVIF where
                    // the driver lacks libheif) without losing the others.
                }
            }
        }

        return $variants;
    }

    /**
     * Delete every derivative recorded for a media row.
     *
     * @param  array<string, array<int, string>>  $variants
     */
    public function delete(array $variants, string $disk = 'public'): void
    {
        $paths = [];

        foreach ($variants as $byWidth) {
            foreach ($byWidth as $path) {
                $paths[] = $path;
            }
        }

        if ($paths !== []) {
            Storage::disk($disk)->delete($paths);
        }
    }

    /**
     * Encoders keyed by format. AVIF is attempted only when the driver can
     * encode it, so systems without libheif quietly produce WebP only.
     *
     * @return array<string, callable>
     */
    private function formats(): array
    {
        $formats = [
            'webp' => fn ($image) => $image->toWebp(quality: self::WEBP_QUALITY),
        ];

        if ($this->supportsAvif()) {
            $formats['avif'] = fn ($image) => $image->toAvif(quality: self::AVIF_QUALITY);
        }

        return $formats;
    }

    private function supportsAvif(): bool
    {
        static $supported = null;

        if ($supported === null) {
            try {
                // A 1x1 encode probe; throws on drivers without AVIF support.
                $this->manager->create(1, 1)->toAvif();
                $supported = true;
            } catch (Throwable) {
                $supported = false;
            }
        }

        return $supported;
    }

    private function isVector(string $path): bool
    {
        return str_ends_with(strtolower($path), '.svg');
    }

    private function isVideo(string $path): bool
    {
        $lower = strtolower($path);

        foreach (['.mp4', '.webm', '.mov', '.m4v', '.ogv'] as $extension) {
            if (str_ends_with($lower, $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strip the extension so derivative names can be appended.
     */
    private function basePath(string $path): string
    {
        $dot = strrpos($path, '.');

        return $dot === false ? $path : substr($path, 0, $dot);
    }
}
