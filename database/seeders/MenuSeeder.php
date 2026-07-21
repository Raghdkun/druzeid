<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Database\Seeders\Support\PageBlueprint;
use Database\Seeders\Support\SectionBuilder as B;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Recreates the navigation extracted from the source site: two dropdowns —
 * مشاريع المنظمة (six children) and تعريف بالمنظمة (three) — plus a direct
 * link to اتصل بنا.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $pages = Page::query()->pluck('id', 'slug');

        $primary = Menu::updateOrCreate(
            ['key' => Menu::PRIMARY],
            ['name' => 'Primary navigation']
        );

        $primary->items()->delete();

        $projects = $primary->items()->create([
            'label' => B::locales([
                'ar' => 'مشاريع المنظمة',
                'en' => 'Our Projects',
                'fr' => 'Nos projets',
                'nl' => 'Onze projecten',
            ]),
            'sort' => 0,
        ]);

        $this->addChildren($projects, $pages, PageBlueprint::projectSlugs());

        $about = $primary->items()->create([
            'label' => B::locales([
                'ar' => 'تعريف بالمنظمة',
                'en' => 'About the Organization',
                'fr' => "À propos de l'organisation",
                'nl' => 'Over de organisatie',
            ]),
            'sort' => 10,
        ]);

        $this->addChildren($about, $pages, PageBlueprint::organizationSlugs());

        $primary->items()->create([
            'label' => B::locales([
                'ar' => 'اتصل بنا',
                'en' => 'Contact Us',
                'fr' => 'Nous contacter',
                'nl' => 'Contact',
            ]),
            'page_id' => $pages['org-call-us'] ?? null,
            'sort' => 20,
        ]);

        // Footer columns reuse the same groupings — the source footer listed
        // "أقســام الموقع" beside a short organization block.
        $this->seedFooterMenu(
            Menu::FOOTER_SECTIONS,
            'Footer — site sections',
            $pages,
            PageBlueprint::projectSlugs()
        );

        $this->seedFooterMenu(
            Menu::FOOTER_ORGANIZATION,
            'Footer — organization',
            $pages,
            [...PageBlueprint::organizationSlugs(), 'org-call-us']
        );

        $this->command->info('  menus: 3 created with '.MenuItem::count().' items');
    }

    /**
     * @param  Collection<string, int>  $pages
     * @param  list<string>  $slugs
     */
    private function addChildren(MenuItem $parent, $pages, array $slugs): void
    {
        foreach ($slugs as $index => $slug) {
            $blueprint = collect(PageBlueprint::all())->firstWhere('slug', $slug);

            $parent->menu->items()->create([
                'parent_id' => $parent->id,
                'label' => B::locales($blueprint['titles']),
                'page_id' => $pages[$slug] ?? null,
                'sort' => $index * 10,
            ]);
        }
    }

    /**
     * @param  Collection<string, int>  $pages
     * @param  list<string>  $slugs
     */
    private function seedFooterMenu(string $key, string $name, $pages, array $slugs): void
    {
        $menu = Menu::updateOrCreate(['key' => $key], ['name' => $name]);
        $menu->items()->delete();

        foreach ($slugs as $index => $slug) {
            $blueprint = collect(PageBlueprint::all())->firstWhere('slug', $slug);

            if ($blueprint === null) {
                continue;
            }

            $menu->items()->create([
                'label' => B::locales($blueprint['titles']),
                'page_id' => $pages[$slug] ?? null,
                'sort' => $index * 10,
            ]);
        }
    }
}
