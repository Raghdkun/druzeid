<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organizational content: مضافة الدروز (guesthouses), مشاريع (projects),
 * عضويات المنظمة (membership tiers) and شركاؤنا (partners).
 */
return new class extends Migration
{
    public function up(): void
    {
        // مضافة الدروز — the traditional guest house, its etiquette and history.
        Schema::create('madafa_entries', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();
            $table->json('name');
            $table->json('summary')->nullable();
            $table->json('history')->nullable();
            $table->json('etiquette')->nullable();

            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->string('country', 2)->nullable();
            $table->string('locality')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->year('established_year')->nullable();
            $table->json('notable_events')->nullable();

            // Recorded video interviews about the madafa.
            $table->json('video_urls')->nullable();

            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['status', 'country']);
        });

        Schema::create('madafa_entry_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('madafa_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);

            $table->unique(['madafa_entry_id', 'media_id']);
        });

        // مشاريع الهوية الدرزية — initiatives supporting Druze identity.
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->json('outcomes')->nullable();

            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();

            // cultural | educational | social | youth | heritage | media
            $table->string('pillar', 30)->nullable();

            // planned | active | completed
            $table->string('stage', 20)->default('planned');

            $table->date('started_on')->nullable();
            $table->date('completed_on')->nullable();

            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['status', 'stage']);
        });

        // عضويات المنظمة — the six documented membership classifications.
        Schema::create('membership_tiers', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('responsibilities')->nullable();

            $table->boolean('is_open_for_applications')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // شركاؤنا والداعمين — published once licensing is finalised.
        Schema::create('partners', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();

            // international_organization | academic_institution | research_centre
            // | news_agency | individual
            $table->string('kind', 40)->nullable();

            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->string('website')->nullable();
            $table->string('country', 2)->nullable();

            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['status', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
        Schema::dropIfExists('membership_tiers');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('madafa_entry_media');
        Schema::dropIfExists('madafa_entries');
    }
};
