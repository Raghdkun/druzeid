import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            // Self-hosted at build time (WOFF2, Arabic + Latin subsets). Cairo
            // + IBM Plex Sans Arabic are the shipped defaults; the rest are the
            // curated menu an admin can pick from per language in Theme
            // settings (must stay in sync with App\Support\Theme::FONTS).
            fonts: [
                bunny('Cairo', { weights: [600, 700, 900] }),
                bunny('IBM Plex Sans Arabic', { weights: [400, 500, 600, 700] }),
                bunny('Tajawal', { weights: [400, 500, 700, 800] }),
                bunny('Almarai', { weights: [400, 700, 800] }),
                bunny('Amiri', { weights: [400, 700] }),
                bunny('Rubik', { weights: [400, 500, 700] }),
                bunny('Inter', { weights: [400, 500, 700] }),
                bunny('Lora', { weights: [400, 600, 700] }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
});
