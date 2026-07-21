<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTranslations;
use App\Support\Locale;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $filename
 * @property string $mime
 * @property int $size
 * @property string $kind
 * @property int|null $width
 * @property int|null $height
 * @property int|null $duration
 * @property array<string, array<int, string>>|null $variants
 * @property string|null $poster_path
 * @property string|null $credit
 * @property float $focal_x
 * @property float $focal_y
 * @property bool $needs_alt_review
 * @property bool $is_decorative
 * @property string|null $checksum
 * @property int|null $legacy_wp_id
 * @property-read string $url
 * @property-read string $object_position
 * @property-read bool $is_video
 * @property-read string|null $poster_url
 */
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    use HasTranslations;

    protected $table = 'media';

    protected $guarded = ['id'];

    /**
     * @return list<string>
     */
    public function getTranslatableAttributes(): array
    {
        return ['alt', 'caption'];
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
            'focal_x' => 'float',
            'focal_y' => 'float',
            'needs_alt_review' => 'boolean',
            'is_decorative' => 'boolean',
            'variants' => 'array',
        ];
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->kind === 'video';
    }

    /**
     * URL of the extracted poster frame, or null when none was generated.
     */
    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster_path
            ? Storage::disk($this->disk)->url($this->poster_path)
            : null;
    }

    /**
     * A `srcset` string for a next-gen format, or null when no derivatives
     * exist for it. Widths come from the stored variant map.
     */
    public function srcset(string $format): ?string
    {
        $byWidth = $this->variants[$format] ?? [];

        if ($byWidth === []) {
            return null;
        }

        $disk = Storage::disk($this->disk);
        $entries = [];

        foreach ($byWidth as $width => $path) {
            $entries[] = $disk->url($path).' '.$width.'w';
        }

        return implode(', ', $entries);
    }

    /**
     * Aspect ratio, used to reserve layout space and avoid CLS.
     */
    public function getAspectRatioAttribute(): ?float
    {
        if (! $this->width || ! $this->height) {
            return null;
        }

        return round($this->width / $this->height, 4);
    }

    /**
     * CSS object-position for the focal point, so art-directed crops keep
     * the subject in frame across breakpoints.
     */
    public function getObjectPositionAttribute(): string
    {
        return round($this->focal_x * 100, 2).'% '.round($this->focal_y * 100, 2).'%';
    }

    /**
     * Pages that reference this image, for the CMS "used on N pages" badge and
     * a delete guard. Matches the id inside section payloads and the page's
     * own hero_media_id.
     *
     * @return list<array{id: int, slug: string, title: ?string}>
     */
    public function usedOnPages(): array
    {
        // Resolve references in PHP rather than with a JSON LIKE query: it is
        // exact (no false positives from substring matches), works identically
        // across MySQL/PostgreSQL/SQLite, and the section count is small.
        $pages = Page::query()
            ->with('sections:id,page_id,payload')
            ->get(['id', 'slug', 'title', 'hero_media_id']);

        return array_values(
            $pages
                ->filter(fn (Page $page) => $page->hero_media_id === $this->id
                    || $page->sections->contains(fn (PageSection $section) => in_array($this->id, $section->mediaIds(), true)))
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->getTranslation('title', Locale::default()),
                ])
                ->all()
        );
    }

    /**
     * Shape consumed by the front-end <Picture> component.
     *
     * @return array<string, mixed>
     */
    public function toImageProps(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            // A decorative image takes an empty alt so screen readers skip it;
            // a meaningful one always carries text.
            'alt' => $this->is_decorative ? '' : ($this->getTranslation('alt') ?? ''),
            'caption' => $this->getTranslation('caption'),
            'credit' => $this->credit,
            'width' => $this->width,
            'height' => $this->height,
            'objectPosition' => $this->object_position,
            // Responsive next-gen sources for the front-end <picture>. Null
            // when no derivatives exist (SVG, video, or pre-derivative records).
            'srcsetAvif' => $this->srcset('avif'),
            'srcsetWebp' => $this->srcset('webp'),
            // Media kind lets the renderer choose <picture> vs the video player;
            // mime/poster/duration are only meaningful for video.
            'kind' => $this->kind,
            'mime' => $this->mime,
            'poster' => $this->poster_url,
            'duration' => $this->duration,
        ];
    }
}
