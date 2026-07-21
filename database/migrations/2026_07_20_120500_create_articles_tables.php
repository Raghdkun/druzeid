<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أخبار الدروز — Druze News (DRUZE-NEWS.com).
 *
 * Schema only in Phase 1; the CMS UI lands in Phase 2. Defined now so the
 * announcement page can be replaced with real content without a migration
 * rewrite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('accent', 20)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('body')->nullable();

            $table->foreignId('article_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            // Editorial provenance — the section brief commits to sourcing
            // every item from verifiable outlets.
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();

            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);

            $table->unsignedBigInteger('legacy_wp_id')->nullable()->unique();

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
    }
};
