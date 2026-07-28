<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The homepage news slider was replaced by the news ticker under the navbar,
 * and the `news_slider` section type was retired. Any pages in production that
 * still hold a `news_slider` section would fail validation on the next save
 * (the type is no longer allowed) and render nothing, so remove them here —
 * an additive data migration, no re-seed required.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('page_sections')->where('type', 'news_slider')->delete();
    }

    public function down(): void
    {
        // The removed sections cannot be reconstructed; nothing to roll back.
    }
};
