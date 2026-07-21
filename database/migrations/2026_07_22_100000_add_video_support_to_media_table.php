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
            // 'image' | 'video'. Kept as a column (rather than derived from the
            // MIME each time) so the library grid and the video picker can
            // filter on it with an indexed, DB-agnostic query.
            $table->string('kind', 16)->default('image')->index()->after('mime');

            // Video only: length in whole seconds and a poster frame extracted
            // at upload. Both are null when ffmpeg is unavailable.
            $table->unsignedInteger('duration')->nullable()->after('height');
            $table->string('poster_path')->nullable()->after('variants');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn(['kind', 'duration', 'poster_path']);
        });
    }
};
