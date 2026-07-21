<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Page;
use App\Support\Locale;

/**
 * Shapes a page for the CMS editor.
 *
 * Unlike the public site, the CMS needs the *raw* translation maps — every
 * locale of every field — so an editor can fill them in side by side, plus a
 * per-locale completeness read for the "translated / missing" indicators.
 */
final class CmsPageResource
{
    /**
     * @return array<string, mixed>
     */
    public static function detail(Page $page): array
    {
        $page->loadMissing('sections');

        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'template' => $page->template,
            'accent' => $page->accent,
            'status' => $page->status,
            'heroMediaId' => $page->hero_media_id,
            'sort' => $page->sort,
            'publishedAt' => $page->published_at?->toIso8601String(),

            // Raw locale maps for every translatable field.
            'title' => $page->getTranslations('title'),
            'excerpt' => $page->getTranslations('excerpt'),
            'seoTitle' => $page->getTranslations('seo_title'),
            'seoDescription' => $page->getTranslations('seo_description'),

            'sections' => $page->sections->map(fn ($section) => [
                'id' => $section->id,
                'type' => $section->type,
                'payload' => $section->payload,
                'sort' => $section->sort,
                'isVisible' => $section->is_visible,
            ])->all(),

            'completeness' => self::completeness($page),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(Page $page): array
    {
        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'template' => $page->template,
            'accent' => $page->accent,
            'status' => $page->status,
            'sort' => $page->sort,
            'title' => $page->getTranslation('title', Locale::default()),
            'sectionCount' => $page->sections_count ?? $page->sections()->count(),
            'updatedAt' => $page->updated_at?->toIso8601String(),
            'completeness' => self::completeness($page),
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function completeness(Page $page): array
    {
        $result = [];

        foreach (Locale::codes() as $code) {
            $result[$code] = (int) round($page->translationCompleteness($code) * 100);
        }

        return $result;
    }
}
