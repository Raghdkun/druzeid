<?php

declare(strict_types=1);

namespace App\Actions\Cms;

use App\Models\Page;
use App\Services\SiteChrome;
use App\Support\Html;
use Illuminate\Support\Facades\DB;

/**
 * Persists a page and its full section list in one transaction.
 *
 * Sections are replaced wholesale rather than diffed: the editor sends the
 * complete ordered list every save, which keeps ordering and deletion trivial
 * and avoids a reconciliation pass. Volume is low enough that this is cheap.
 */
class SavePage
{
    /**
     * @param  array<string, mixed>  $data  validated PageRequest data
     */
    public function handle(Page $page, array $data): Page
    {
        return DB::transaction(function () use ($page, $data): Page {
            $page->fill([
                'slug' => $data['slug'],
                'template' => $data['template'],
                'accent' => $data['accent'] ?? null,
                'status' => $data['status'],
                'hero_media_id' => $data['heroMediaId'] ?? null,
                'sort' => $data['sort'] ?? $page->sort ?? 0,
            ]);

            // Translatable maps — arrays replace the whole set; the model's
            // HasTranslations prunes empty values and unsupported locales.
            $page->setTranslations('title', $data['title']);
            $page->setTranslations('excerpt', $data['excerpt'] ?? []);
            $page->setTranslations('seo_title', $data['seoTitle'] ?? []);
            $page->setTranslations('seo_description', $data['seoDescription'] ?? []);

            if ($page->status === Page::STATUS_PUBLISHED && $page->published_at === null) {
                $page->published_at = now();
            }

            $page->save();

            $page->sections()->delete();

            foreach ($data['sections'] as $index => $section) {
                $page->sections()->create([
                    'type' => $section['type'],
                    // Sanitise any rich-text HTML in the payload before storing,
                    // so the public renderer can trust it and no script can be
                    // injected through a section.
                    'payload' => self::sanitizePayload($section['payload']),
                    'sort' => $index * 10,
                    'is_visible' => $section['isVisible'] ?? true,
                ]);
            }

            // A saved page can change nav labels and menu targets.
            SiteChrome::flush();

            return $page->fresh(['sections']);
        });
    }

    /**
     * Sanitise every `html` field anywhere in a section payload. Any value
     * under an `html` key is treated as rich text and cleaned against the
     * editor allowlist; a locale map of HTML is cleaned per locale.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function sanitizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($key === 'html') {
                $payload[$key] = is_array($value)
                    ? Html::cleanMap($value)
                    : Html::clean(is_string($value) ? $value : null);

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = self::sanitizePayload($value);
            }
        }

        return $payload;
    }
}
