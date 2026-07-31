<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories gain a representative image so a dynamic hero slide that points at
 * a category has a picture to show (categories otherwise carry only name and
 * description). Nullable — a category without one falls back to a brand panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_categories', function (Blueprint $table): void {
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('article_categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('image_media_id');
        });
    }
};
