<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_sections', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('page_id')->constrained()->cascadeOnDelete();

            // hero | rich_text | card_grid | stat_band | cta | gallery
            // | accordion | contact | under_construction
            $table->string('type', 40);

            /*
             * All section content lives here. Any text value is stored as a
             * locale map — {"ar": "…", "en": "…"} — at whatever depth it
             * appears, including inside repeatable item arrays. App\Support\
             * Translate collapses those maps for the active locale before the
             * payload reaches React, so the renderer never sees translations.
             *
             * This replaces Colibri's postmeta layout blobs, which were the
             * reason the original content could not be edited structurally.
             */
            $table->json('payload');

            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);

            $table->timestamps();

            $table->index(['page_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
