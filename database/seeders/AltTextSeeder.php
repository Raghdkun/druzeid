<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

/**
 * Provisional alt text for imported images.
 *
 * None of the source images had alt text. Rather than leave 153 blanks or
 * invent specifics — the names, dates and places in these archival photographs
 * cannot be verified from the files — this seeder writes an honest,
 * context-derived description ("صورة من قسم أرشيف الدروز" / "Photograph from
 * the Druze Archives section") based on the page each image appears on.
 *
 * Every record keeps `needs_alt_review = true`, so the CMS surfaces them as a
 * queue for an editor who knows the subjects to replace the placeholder with a
 * true description.
 */
class AltTextSeeder extends Seeder
{
    /**
     * Hand-written, verified descriptions for the small set of images that
     * are visually confirmed and load-bearing (the home hero rotation). These
     * describe only what is plainly visible — no attributed identities.
     *
     * @var array<int, array{ar: string, en: string}>
     */
    private const VERIFIED = [
        // 8d865330 — large formal gathering on steps before a building.
        64 => [
            'ar' => 'صورة تاريخية بالأبيض والأسود لتجمّع كبير من وجهاء الموحدين الدروز بالزيّ التقليدي',
            'en' => 'Historic black-and-white photograph of a large gathering of Druze notables in traditional dress',
        ],
    ];

    public function run(): void
    {
        // Map each media id to the page it appears on, via the seeded sections.
        $pageByMedia = $this->pageByMedia();
        $updated = 0;

        foreach (Media::query()->get() as $media) {
            if (isset(self::VERIFIED[$media->id])) {
                $alt = self::VERIFIED[$media->id];
            } else {
                $alt = $this->contextualAlt($pageByMedia[$media->id] ?? null);
            }

            $media->setTranslations('alt', $alt);
            $media->needs_alt_review = true;   // provisional until a human confirms
            $media->save();
            $updated++;
        }

        $reviewCount = Media::query()->where('needs_alt_review', true)->count();
        $this->command->info("  alt text: {$updated} provisional descriptions written, {$reviewCount} queued for review");
    }

    /**
     * @param  array{ar: string, en: string}|null  $titles
     * @return array{ar: string, en: string}
     */
    private function contextualAlt(?array $titles): array
    {
        if ($titles === null) {
            return [
                'ar' => 'صورة من موقع منظمة الهوية الدرزية',
                'en' => 'Image from the Druze Identity Organization website',
            ];
        }

        return [
            'ar' => "صورة من قسم «{$titles['ar']}»",
            'en' => "Photograph from the “{$titles['en']}” section",
        ];
    }

    /**
     * Walk every section payload and record which page each media id sits on,
     * so the provisional alt can name the section.
     *
     * @return array<int, array{ar: string, en: string}>
     */
    private function pageByMedia(): array
    {
        $map = [];

        $pages = Page::query()->with('sections')->get();

        foreach ($pages as $page) {
            $titles = [
                'ar' => $page->getTranslation('title', 'ar') ?? '',
                'en' => $page->getTranslation('title', 'en') ?? ($page->getTranslation('title', 'ar') ?? ''),
            ];

            foreach ($page->sections as $section) {
                foreach ($this->mediaIdsIn($section) as $id) {
                    // First page wins — matches display priority.
                    $map[$id] ??= $titles;
                }
            }
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    private function mediaIdsIn(PageSection $section): array
    {
        return $this->collectIds($section->payload);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<int>
     */
    private function collectIds(array $node): array
    {
        $ids = [];

        foreach ($node as $key => $value) {
            if (($key === 'media_id' || str_ends_with((string) $key, '_media_id')) && is_int($value)) {
                $ids[] = $value;
            } elseif ($key === 'media_ids' && is_array($value)) {
                $ids = [...$ids, ...array_filter($value, 'is_int')];
            } elseif (is_array($value)) {
                $ids = [...$ids, ...$this->collectIds($value)];
            }
        }

        return array_values(array_unique($ids));
    }
}
