<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Rebuilds the site from the `_extracted/` bundle.
     *
     * Order matters: media first so pages can reference images, settings
     * before pages so the contact page can read them, pages before menus so
     * menu items can link to real page ids.
     */
    public function run(): void
    {
        $this->call([
            MediaSeeder::class,
            SettingSeeder::class,
            PageSeeder::class,
            MenuSeeder::class,
            // After pages exist, so provisional alt text can name the section
            // each image appears on.
            AltTextSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->seedStaff();
        }
    }

    /**
     * Seed one admin and one editor locally, so both roles can be exercised.
     * Registration is disabled, so all accounts are created this way or from
     * the CMS users screen.
     */
    private function seedStaff(): void
    {
        $staff = [
            ['مدير الموقع', 'admin@druze.id', User::ROLE_ADMIN],
            ['محرّر المحتوى', 'editor@druze.id', User::ROLE_EDITOR],
        ];

        $created = [];

        foreach ($staff as [$name, $email, $role]) {
            if (User::query()->where('email', $email)->exists()) {
                continue;
            }

            User::factory()->create(['name' => $name, 'email' => $email, 'role' => $role]);
            $created[] = "{$email} ({$role})";
        }

        if ($created !== []) {
            $this->command->info('  users: created '.implode(', ', $created).' — password: "password"');
        }
    }
}
