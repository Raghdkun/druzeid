<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Translate;
use Database\Factories\PageSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $page_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property int $sort
 * @property bool $is_visible
 */
class PageSection extends Model
{
    /** @use HasFactory<PageSectionFactory> */
    use HasFactory;

    /**
     * Section types the front-end renderer knows how to draw. Adding one means
     * adding a component and an entry in resources/js/components/sections.
     */
    public const TYPES = [
        'hero',
        'rich_text',
        'card_grid',
        'stat_band',
        'cta',
        'gallery',
        'video',
        'accordion',
        'contact',
        'under_construction',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_visible' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Payload with every nested locale map collapsed for the active locale,
     * plus any referenced media hydrated into image props. This is what the
     * public site receives.
     *
     * @return array<string, mixed>
     */
    public function resolvedPayload(?string $locale = null): array
    {
        $payload = Translate::resolve($this->payload ?? [], $locale);

        return $this->hydrateMedia(is_array($payload) ? $payload : []);
    }

    /**
     * Replace `*_media_id` / `media_ids` references with full image props so
     * the renderer never has to make a second query.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function hydrateMedia(array $payload): array
    {
        $ids = $this->collectMediaIds($payload);

        if ($ids === []) {
            return $payload;
        }

        $media = Media::query()->whereIn('id', $ids)->get()->keyBy('id');

        return $this->replaceMediaIds($payload, $media);
    }

    /**
     * Every media id this section references, across all its payload keys.
     *
     * @return list<int>
     */
    public function mediaIds(): array
    {
        return $this->collectMediaIds($this->payload ?? []);
    }

    /**
     * Walk the payload collecting every media reference. Written as an
     * explicit recursion rather than array_walk_recursive, which only yields
     * leaf keys and so would never see a `media_ids` list.
     *
     * @param  array<string, mixed>  $payload
     * @return list<int>
     */
    protected function collectMediaIds(array $payload): array
    {
        $ids = [];

        foreach ($payload as $key => $value) {
            if ($this->isSingleMediaKey($key) && is_int($value)) {
                $ids[] = $value;

                continue;
            }

            if ($key === 'media_ids' && is_array($value)) {
                $ids = [...$ids, ...array_filter($value, 'is_int')];

                continue;
            }

            if (is_array($value)) {
                $ids = [...$ids, ...$this->collectMediaIds($value)];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, Media>  $media
     * @return array<string, mixed>
     */
    protected function replaceMediaIds(array $payload, $media): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            $result[$key] = $value;

            // media_id -> image, hero_media_id -> hero_image
            if ($this->isSingleMediaKey($key) && is_int($value)) {
                $imageKey = $key === 'media_id'
                    ? 'image'
                    : substr((string) $key, 0, -strlen('_media_id')).'_image';

                $result[$imageKey] = $media->get($value)?->toImageProps();

                continue;
            }

            // media_ids -> images, preserving the authored order
            if ($key === 'media_ids' && is_array($value)) {
                $result['images'] = array_values(array_filter(array_map(
                    fn ($id) => is_int($id) ? $media->get($id)?->toImageProps() : null,
                    $value
                )));

                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->replaceMediaIds($value, $media);
            }
        }

        return $result;
    }

    protected function isSingleMediaKey(int|string $key): bool
    {
        return $key === 'media_id' || str_ends_with((string) $key, '_media_id');
    }
}
