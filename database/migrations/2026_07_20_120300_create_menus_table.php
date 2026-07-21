<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();   // primary | footer_sections | footer_org
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();

            $table->json('label');

            // Either an internal page reference or a literal URL — a dropdown
            // parent has neither and simply groups its children.
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url')->nullable();

            $table->boolean('opens_new_tab')->default(false);
            $table->unsignedInteger('sort')->default(0);

            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
