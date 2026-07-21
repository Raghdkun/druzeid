<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();

            // A single slug serves every locale, matching the original site
            // (/org-about-us resolves in ar, en, fr and nl alike). Keeps the
            // catch-all route unambiguous and avoids per-locale redirects.
            $table->string('slug')->unique();

            $table->string('template')->default('default');

            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();

            // Which Druze star colour this section is keyed to, if any.
            $table->string('accent', 20)->nullable();

            $table->foreignId('hero_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort')->default(0);

            $table->unsignedBigInteger('legacy_wp_id')->nullable()->unique();

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
