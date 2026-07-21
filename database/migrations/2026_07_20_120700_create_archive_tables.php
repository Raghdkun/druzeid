<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أرشيف الدروز — Druze Archives (DRUZE-ARCHIVES.com).
 *
 * The source page names two collections explicitly: النشاطات والمبادرات
 * (activities and initiatives) and القوانين والأنظمة (laws and regulations
 * affecting Druze communities across states), with heritage documentation
 * as the broader purpose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_collections', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('accent', 20)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('archive_items', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('body')->nullable();

            $table->foreignId('archive_collection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();

            // document | photograph | testimony | law | activity
            $table->string('item_type', 30)->default('document');

            // Provenance matters more here than anywhere else on the site —
            // the archive's whole claim is that it is citable.
            $table->json('provenance')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('locality')->nullable();

            // Historical dating is frequently imprecise, so a display string
            // sits alongside the sortable date.
            $table->date('dated_on')->nullable();
            $table->json('date_display')->nullable();
            $table->year('period_start')->nullable();
            $table->year('period_end')->nullable();

            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['status', 'item_type']);
            $table->index(['period_start', 'period_end']);
        });

        Schema::create('archive_item_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('archive_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);

            $table->unique(['archive_item_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_item_media');
        Schema::dropIfExists('archive_items');
        Schema::dropIfExists('archive_collections');
    }
};
