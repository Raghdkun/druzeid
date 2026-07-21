<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();

            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Alt text is translatable and required in the default locale —
            // none of the 141 images on the WordPress original had any.
            $table->json('alt')->nullable();
            $table->json('caption')->nullable();
            $table->string('credit')->nullable();

            // Imported images get a provisional, context-derived alt string.
            // Describing photographs of real people without having seen them
            // would be fabrication, so they are flagged for a human pass and
            // surfaced as a queue in the CMS.
            $table->boolean('needs_alt_review')->default(false)->index();
            $table->boolean('is_decorative')->default(false);

            // Focal point for art-directed crops, 0–1 in each axis.
            $table->float('focal_x')->default(0.5);
            $table->float('focal_y')->default(0.5);

            // Provenance from the WordPress import, so a re-import can
            // reconcile rather than duplicate.
            $table->unsignedBigInteger('legacy_wp_id')->nullable()->unique();
            $table->string('checksum', 64)->nullable()->index();

            $table->timestamps();

            $table->index(['mime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
