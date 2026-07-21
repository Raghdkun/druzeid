<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دليل المجتمع الدرزي — Druze Community Directory.
 *
 * The source page describes "a database of associations, organizations,
 * institutions and active figures within the Druze community worldwide",
 * whose stated aims are introduction, connection, cooperation and access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('directory_entries', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();

            // association | organization | institution | figure
            $table->string('kind', 30)->default('organization');

            $table->json('name');
            $table->json('description')->nullable();
            $table->json('role')->nullable();          // for people
            $table->json('address')->nullable();

            $table->foreignId('directory_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->string('country', 2)->nullable();  // ISO 3166-1 alpha-2
            $table->string('city')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->json('social')->nullable();        // {facebook: url, …}

            $table->year('founded_year')->nullable();

            // Entries are published only once the organization confirms them.
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['status', 'country']);
            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_entries');
        Schema::dropIfExists('directory_categories');
    }
};
