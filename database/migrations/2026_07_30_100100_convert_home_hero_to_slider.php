<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The homepage hero becomes a `hero_slider`: its title/subtitle/actions and its
 * first background image are carried into a single manual slide, in place (the
 * section keeps its id, sort and visibility). Guarded and idempotent — it only
 * touches the home page's `hero` section, leaves other pages' heroes alone, and
 * does nothing if there is no home hero (or it is already a slider). The extra
 * rotating backgrounds stay in the media library to add as further slides.
 */
return new class extends Migration
{
    public function up(): void
    {
        $homePageId = DB::table('pages')->where('template', 'home')->value('id');

        if ($homePageId === null) {
            return;
        }

        $hero = DB::table('page_sections')
            ->where('page_id', $homePageId)
            ->where('type', 'hero')
            ->orderBy('sort')
            ->first();

        if ($hero === null) {
            return;
        }

        $payload = json_decode((string) $hero->payload, true);
        $payload = is_array($payload) ? $payload : [];

        $mediaIds = is_array($payload['media_ids'] ?? null)
            ? array_values(array_filter($payload['media_ids'], 'is_int'))
            : [];

        $slide = [
            'kind' => 'manual',
            'media_id' => $mediaIds[0] ?? null,
            'title' => $payload['title'] ?? (object) [],
            'subtitle' => $payload['subtitle'] ?? (object) [],
            'actions' => $payload['actions'] ?? [],
        ];

        DB::table('page_sections')->where('id', $hero->id)->update([
            'type' => 'hero_slider',
            'payload' => json_encode(['slides' => [$slide]], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $homePageId = DB::table('pages')->where('template', 'home')->value('id');

        if ($homePageId === null) {
            return;
        }

        $slider = DB::table('page_sections')
            ->where('page_id', $homePageId)
            ->where('type', 'hero_slider')
            ->orderBy('sort')
            ->first();

        if ($slider === null) {
            return;
        }

        $payload = json_decode((string) $slider->payload, true);
        $slide = is_array($payload['slides'][0] ?? null) ? $payload['slides'][0] : [];

        $mediaId = $slide['media_id'] ?? null;

        DB::table('page_sections')->where('id', $slider->id)->update([
            'type' => 'hero',
            'payload' => json_encode([
                'title' => $slide['title'] ?? (object) [],
                'subtitle' => $slide['subtitle'] ?? (object) [],
                'media_ids' => is_int($mediaId) ? [$mediaId] : [],
                'actions' => $slide['actions'] ?? [],
            ], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }
};
