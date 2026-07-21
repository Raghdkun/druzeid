<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Support\ArabicText;
use Database\Seeders\Support\ContentDictionary;
use Database\Seeders\Support\ExtractedPage;
use Database\Seeders\Support\PageBlueprint;
use Database\Seeders\Support\SectionBuilder as B;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PageSeeder extends Seeder
{
    private string $bundle;

    /** @var array<string, list<string>> slug => original image filenames */
    private array $pageImages = [];

    /** @var array<string, int> original filename => media id */
    private array $mediaByFilename = [];

    public function run(): void
    {
        $this->bundle = base_path('_extracted');
        $this->loadImageIndex();

        $created = 0;

        foreach (PageBlueprint::all() as $blueprint) {
            $this->seedPage($blueprint);
            $created++;
        }

        $this->command->info("  pages: {$created} created with ".PageSection::count().' sections');
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    private function seedPage(array $blueprint): void
    {
        $slug = $blueprint['slug'];
        $extracted = ExtractedPage::load($slug, $this->bundle);
        $blocks = B::dropRedundantLead($extracted->blocks(), $blueprint['titles']['ar']);

        $images = $this->mediaIdsFor($slug);

        $page = Page::updateOrCreate(
            ['slug' => $slug],
            [
                'template' => $blueprint['template'],
                'accent' => $blueprint['accent'],
                'title' => B::locales($blueprint['titles']),
                'excerpt' => $this->deriveExcerptMap($blocks),
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'sort' => $blueprint['sort'],
                'hero_media_id' => $images[0] ?? null,
            ]
        );

        $page->sections()->delete();

        $sections = match ($blueprint['template']) {
            'home' => $this->homeSections($blocks, $images),
            'under_construction' => $this->announcementSections($extracted, $blocks, $images),
            'contact' => $this->contactSections($extracted, $blocks),
            default => $this->defaultSections($blocks, $images),
        };

        foreach ($sections as $index => $section) {
            $page->sections()->create([
                'type' => $section['type'],
                'payload' => $section['payload'],
                'sort' => $index * 10,
                'is_visible' => true,
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Templates
    // -----------------------------------------------------------------

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  list<int>  $images
     * @return list<array<string, mixed>>
     */
    private function homeSections(array $blocks, array $images): array
    {
        // The source home page reads as: tagline, mission statement, a
        // welcome couplet, then the "why a global identity" essay. The
        // welcome couplet is where the section cards belong.
        $welcomeIndex = $this->indexOfBlockContaining($blocks, 'شكرا لزيارتكم');
        $essayIndex = $this->indexOfBlockContaining($blocks, 'لماذا يحتاج الدروز');

        $intro = array_slice($blocks, 1, ($welcomeIndex ?? 3) - 1);
        $welcome = $welcomeIndex !== null
            ? array_slice($blocks, $welcomeIndex, ($essayIndex ?? count($blocks)) - $welcomeIndex)
            : [];
        $essay = $essayIndex !== null ? array_slice($blocks, $essayIndex) : [];

        return array_values(array_filter([
            [
                'type' => 'hero',
                'payload' => [
                    'title' => B::locales([
                        'ar' => 'منظمة الهوية الدرزية',
                        'en' => 'Druze Identity Organization',
                        'fr' => "Organisation de l'Identité Druze",
                        'nl' => 'Druzische Identiteitsorganisatie',
                    ]),
                    'subtitle' => B::tr($blocks[0]['text'] ?? null),
                    // The original rotated four historic photographs here.
                    'media_ids' => array_slice($images, 0, 4),
                    'actions' => [
                        [
                            'label' => B::locales([
                                'ar' => 'أقســام الموقع',
                                'en' => 'Site sections',
                                'fr' => 'Sections du site',
                                'nl' => 'Sitesecties',
                            ]),
                            'href' => '#sections',
                        ],
                        [
                            'label' => B::locales([
                                'ar' => 'تعريف بالمنظمة',
                                'en' => 'About the organization',
                                'fr' => "À propos de l'organisation",
                                'nl' => 'Over de organisatie',
                            ]),
                            'slug' => 'org-about-us',
                        ],
                    ],
                ],
            ],
            $intro === [] ? null : ['type' => 'rich_text', 'payload' => B::richText($intro)],
            [
                'type' => 'card_grid',
                'payload' => [
                    'anchor' => 'sections',
                    'heading' => B::tr($welcome[0]['text'] ?? 'أقســام الموقع'),
                    'intro' => B::tr($welcome[1]['text'] ?? null),
                    'cards' => $this->projectCards(),
                ],
            ],
            $essay === [] ? null : [
                'type' => 'rich_text',
                'payload' => [
                    'variant' => 'feature',
                    ...B::richText($essay),
                ],
            ],
        ]));
    }

    /**
     * The five "قيد الإنشاء" pages: a short description of a site that does
     * not exist yet, then its planned sections.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @param  list<int>  $images
     * @return list<array<string, mixed>>
     */
    private function announcementSections(ExtractedPage $extracted, array $blocks, array $images): array
    {
        $split = B::splitAtHeadings($blocks);

        $sections = [
            [
                'type' => 'under_construction',
                'payload' => [
                    'domain' => $extracted->targetDomain(),
                    'media_id' => $images[0] ?? null,
                    'html' => B::htmlFromBlocks($split['intro']),
                ],
            ],
        ];

        if ($split['groups'] !== []) {
            $sections[] = [
                'type' => 'accordion',
                'payload' => B::accordion($split['groups'], 'الأقسام المخطط لها'),
            ];
        }

        return $sections;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function contactSections(ExtractedPage $extracted, array $blocks): array
    {
        $details = $extracted->contactDetails();

        // Drop the raw "label : value" lines — the contact section renders
        // those as structured, actionable links instead.
        $prose = array_values(array_filter(
            $blocks,
            fn (array $block) => $block['kind'] !== 'paragraph'
                || ! preg_match('/[\w.\-]+@[\w.\-]+\.\w+|\b00\d{9,}/u', $block['text'])
        ));

        return [[
            'type' => 'contact',
            'payload' => [
                'email' => $details['emails'][0] ?? null,
                'phones' => $details['phones'],
                'html' => B::htmlFromBlocks($prose),
                'showForm' => true,
            ],
        ]];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  list<int>  $images
     * @return list<array<string, mixed>>
     */
    private function defaultSections(array $blocks, array $images): array
    {
        $sections = [['type' => 'rich_text', 'payload' => B::richText($blocks)]];

        // Surface the page's own photographs rather than dropping them.
        $gallery = array_slice($images, 1, 6);

        if (count($gallery) >= 3) {
            $sections[] = ['type' => 'gallery', 'payload' => ['media_ids' => $gallery]];
        }

        return $sections;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Cards linking the six project sections, in navigation order.
     *
     * @return list<array<string, mixed>>
     */
    private function projectCards(): array
    {
        $cards = [];

        foreach (PageBlueprint::projectSlugs() as $slug) {
            $blueprint = collect(PageBlueprint::all())->firstWhere('slug', $slug);
            $extracted = ExtractedPage::load($slug, $this->bundle);
            $images = $this->mediaIdsFor($slug);

            $cards[] = [
                'slug' => $slug,
                'title' => B::locales($blueprint['titles']),
                'accent' => $blueprint['accent'],
                'media_id' => $images[0] ?? null,
                'excerpt' => $this->deriveExcerptMap(
                    B::dropRedundantLead($extracted->blocks(), $blueprint['titles']['ar'])
                ),
                // Derived from the template rather than the text marker: the
                // directory page describes a planned section without ever
                // using the words "قيد الإنشاء", but it is just as unbuilt.
                'underConstruction' => $blueprint['template'] === 'under_construction',
            ];
        }

        return $cards;
    }

    /**
     * A card-sized excerpt as a locale map: the first substantial paragraph,
     * translated via the dictionary and trimmed per locale so English/French/
     * Dutch cards read in their own language, not the Arabic fallback.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, string>
     */
    private function deriveExcerptMap(array $blocks): array
    {
        $source = null;

        foreach ($blocks as $block) {
            if ($block['kind'] === 'paragraph' && mb_strlen($block['text']) > 60) {
                $source = $block['text'];
                break;
            }
        }

        $source ??= collect($blocks)->firstWhere('kind', 'paragraph')['text'] ?? null;

        if ($source === null) {
            return [];
        }

        $trim = fn (string $text): string => mb_strlen($text) > 180
            ? rtrim(mb_substr($text, 0, 180), " \t\n،,.").'…'
            : $text;

        $map = ['ar' => $trim($source)];

        foreach (ContentDictionary::translations($source) as $locale => $value) {
            if (filled($value)) {
                $map[$locale] = $trim($value);
            }
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function indexOfBlockContaining(array $blocks, string $needle): ?int
    {
        $needleKey = ArabicText::key($needle);

        foreach ($blocks as $index => $block) {
            if ($block['kind'] !== 'paragraph') {
                continue;
            }

            if (str_contains(ArabicText::key($block['text']), $needleKey)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Media ids for a page, chrome excluded, in the order they appeared.
     *
     * @return list<int>
     */
    private function mediaIdsFor(string $slug): array
    {
        $ids = [];

        foreach ($this->pageImages[$slug] ?? [] as $filename) {
            $id = $this->mediaByFilename[$filename] ?? null;

            if ($id !== null && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Build the page → image index from the extraction manifest, excluding
     * images that appear on most pages: those are the logo and other site
     * furniture, not page content.
     */
    private function loadImageIndex(): void
    {
        $this->mediaByFilename = Media::query()
            ->pluck('id', 'filename')
            ->map(fn ($id) => (int) $id)
            ->all();

        $manifestPath = "{$this->bundle}/manifest.json";

        if (! File::exists($manifestPath)) {
            return;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $pages = [...($manifest['pages'] ?? []), ...($manifest['posts'] ?? [])];

        $perPage = [];
        $frequency = [];

        foreach ($pages as $page) {
            $filenames = array_map(
                fn (string $url) => rawurldecode(basename(parse_url($url, PHP_URL_PATH) ?: '')),
                $page['images'] ?? []
            );

            $perPage[$page['slug']] = $filenames;

            foreach (array_unique($filenames) as $filename) {
                $frequency[$filename] = ($frequency[$filename] ?? 0) + 1;
            }
        }

        // Present on more than half the pages ⇒ site chrome.
        $threshold = max(2, (int) floor(count($pages) * 0.5));
        $chrome = array_keys(array_filter($frequency, fn (int $n) => $n > $threshold));

        foreach ($perPage as $slug => $filenames) {
            $this->pageImages[$slug] = array_values(array_diff($filenames, $chrome));
        }
    }
}
