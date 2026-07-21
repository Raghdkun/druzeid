<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            // Responsive derivatives, shaped as
            // { "webp": {"480": "path", "960": "path"}, "avif": {...} }.
            // Generated on import/upload; empty for SVG and vector formats.
            $table->json('variants')->nullable()->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn('variants');
        });
    }
};
