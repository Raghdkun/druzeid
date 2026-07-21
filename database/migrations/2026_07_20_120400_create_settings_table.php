<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();

            $table->string('key')->unique();
            $table->string('group', 40)->default('general');

            // Wrapped as {"value": …} so scalars, arrays and locale maps all
            // round-trip through one JSON column.
            $table->json('value')->nullable();

            $table->timestamps();

            $table->index('group');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('editor')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });

        Schema::dropIfExists('settings');
    }
};
