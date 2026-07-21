<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الإقتصاد الدرزي — Druze Economy (DRUZE-BUSINESS.com).
 *
 * The source page enumerates its investment categories directly: energy,
 * water, waste sorting and recycling, subsurface resources, and agriculture —
 * seeded as the initial business_categories rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('business_listings', function (Blueprint $table): void {
            $table->id();

            $table->string('slug')->unique();
            $table->json('name');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();

            // business | project | investment_opportunity
            $table->string('kind', 30)->default('business');

            $table->foreignId('business_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->json('owner_name')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();

            // Investment opportunities carry an indicative figure; storing the
            // currency separately keeps it formattable per locale.
            $table->decimal('investment_amount', 15, 2)->nullable();
            $table->string('investment_currency', 3)->nullable();
            $table->boolean('seeking_investment')->default(false);

            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['status', 'kind']);
            $table->index('seeking_investment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_listings');
        Schema::dropIfExists('business_categories');
    }
};
