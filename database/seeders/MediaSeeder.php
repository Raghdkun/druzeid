<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Media;
use App\Services\ImageVariants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imports the image library extracted from the WordPress original.
 *
 * Files come from `_extracted/media/` and their metadata from
 * `_extracted/raw/media.json`. Every image is copied onto the public disk
 * under a stable, ASCII-safe path — the source filenames are percent-encoded
 * Arabic, which is fragile across filesystems and CDNs.
 */
class MediaSeeder extends Seeder
{
    public function run(ImageVariants $variants): void
    {
        $bundle = base_path('_extracted');
        $sourceDir = "{$bundle}/media";

        if (! File::isDirectory($sourceDir)) {
            $this->command->warn("  media: {$sourceDir} not found — skipping");

            return;
        }

        $metadata = $this->metadataByFilename("{$bundle}/raw/media.json");
        $disk = Storage::disk('public');
        $imported = $skipped = 0;

        $invalid = 0;

        foreach (File::files($sourceDir) as $file) {
            $original = $file->getFilename();
            $extension = Str::lower($file->getExtension());

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
                continue;
            }

            // Skip files that are not actually images. A few source downloads
            // returned an HTML error page saved with a .png extension; a real
            // raster image has readable dimensions (SVG is text, so exempt).
            if ($extension !== 'svg' && getimagesize($file->getPathname()) === false) {
                $invalid++;

                continue;
            }

            $checksum = hash_file('sha256', $file->getPathname());

            if ($checksum === false) {
                continue;
            }

            if (Media::query()->where('checksum', $checksum)->exists()) {
                $skipped++;

                continue;
            }

            $meta = $metadata[rawurldecode($original)] ?? $metadata[$original] ?? [];

            // Stable ASCII path; the checksum prefix also de-duplicates
            // the several near-identical "cropped-cropped-…" variants.
            $name = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'image';
            $path = 'media/'.substr($checksum, 0, 8).'-'.Str::limit($name, 60, '').'.'.$extension;

            $disk->put($path, File::get($file->getPathname()));

            [$width, $height] = $this->dimensions($file->getPathname(), $meta);

            Media::create([
                'disk' => 'public',
                'path' => $path,
                'filename' => $original,
                'mime' => $meta['mime'] ?? File::mimeType($file->getPathname()) ?: 'image/jpeg',
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'variants' => $variants->generate($path),
                'alt' => [],
                'caption' => [],
                'checksum' => $checksum,
                'legacy_wp_id' => $meta['id'] ?? null,
                // Nothing on the source site had alt text, so every import
                // starts in the review queue rather than pretending otherwise.
                'needs_alt_review' => true,
            ]);

            $imported++;
        }

        $this->command->info("  media: {$imported} imported, {$skipped} duplicates skipped, {$invalid} invalid skipped");
    }

    /**
     * Index the extracted REST metadata by its original filename.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function metadataByFilename(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $rows = json_decode(File::get($path), true);

        if (! is_array($rows)) {
            return [];
        }

        $indexed = [];

        foreach ($rows as $row) {
            $filename = rawurldecode(basename((string) ($row['source_url'] ?? '')));

            if ($filename === '') {
                continue;
            }

            $details = $row['media_details'] ?? [];

            $indexed[$filename] = [
                'id' => $row['id'] ?? null,
                'mime' => $row['mime_type'] ?? null,
                'width' => $details['width'] ?? null,
                'height' => $details['height'] ?? null,
            ];
        }

        return $indexed;
    }

    /**
     * Prefer the recorded dimensions, falling back to reading the file.
     *
     * @param  array<string, mixed>  $meta
     * @return array{0: ?int, 1: ?int}
     */
    protected function dimensions(string $path, array $meta): array
    {
        if (! empty($meta['width']) && ! empty($meta['height'])) {
            return [(int) $meta['width'], (int) $meta['height']];
        }

        $size = @getimagesize($path);

        return $size === false ? [null, null] : [$size[0], $size[1]];
    }
}
