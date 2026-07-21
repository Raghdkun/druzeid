<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Support\ArabicText;

/**
 * Reads one page out of the `_extracted/` bundle and turns its flat text into
 * structured blocks.
 *
 * The original content lived in Colibri page-builder postmeta, so there is no
 * clean HTML to convert. What survived extraction is the rendered text, which
 * carries reliable structural signals the source author used consistently:
 *
 *   - headings terminated by a run of underscores
 *   - numbered / bulleted list items ("1- …", "A – …", "-…")
 *   - a target domain line (www.DRUZE-ARCHIVES.com)
 *   - an "under construction" marker, tatweel-stretched
 *
 * Parsing those signals preserves every word of the source while recovering
 * real structure — nothing is transcribed by hand, so nothing can drift.
 */
final class ExtractedPage
{
    /** Navigation and footer text repeated on every extracted page. */
    private const CHROME = [
        'منظمة الهوية الدرزية', 'Skip to content',
        'العربية', 'English', 'Français', 'Nederlands',
        'مشاريع المنظمة', 'مشاريع الهوية الدرزية', 'دليل المجتمع الدرزي',
        'أرشيف الدروز', 'الإقتصاد الدرزي', 'أخبار الدروز', 'مضافة الدروز',
        'تعريف بالمنظمة', 'من نحن', 'شركاؤنا والداعمين', 'عضويات المنظمة',
        'اتصل بنا', 'Colibri', 'No Category', 'التفاصيل',
        'عودة للرئيسية', 'عودة إلى الرئيسية', 'الصفحة التالية', 'أقســام الموقع',
    ];

    /** @param list<string> $lines */
    private function __construct(
        public readonly string $slug,
        public readonly array $lines,
    ) {}

    public static function load(string $slug, string $bundlePath): self
    {
        $matches = glob("{$bundlePath}/content/page-*-{$slug}.md")
            ?: glob("{$bundlePath}/content/post-*-{$slug}.md");

        if ($matches === false || $matches === []) {
            return new self($slug, []);
        }

        return new self($slug, self::cleanLines((string) file_get_contents($matches[0])));
    }

    /**
     * @return list<string>
     */
    private static function cleanLines(string $markdown): array
    {
        $body = explode('## Rendered page text', $markdown, 2)[1] ?? '';
        $body = explode('## Images on this page', $body, 2)[0];

        $chromeKeys = array_map(ArabicText::key(...), self::CHROME);
        $lines = [];

        foreach (explode("\n", $body) as $raw) {
            $line = trim($raw);

            if ($line === '' || $line === '”' || $line === '"') {
                continue;
            }

            if (in_array(ArabicText::key($line), $chromeKeys, true)) {
                continue;
            }

            if (str_starts_with($line, '© ') || str_contains($line, 'Created for free')) {
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * The target domain this section announces, if any.
     */
    public function targetDomain(): ?string
    {
        foreach ($this->lines as $line) {
            if (preg_match('/\bwww\.[A-Za-z0-9\-]+\.[A-Za-z]{2,}\b/u', $line, $m)) {
                return $m[0];
            }
        }

        return null;
    }

    /**
     * True when the page announces a site that does not exist yet. The source
     * stretches the phrase with tatweel — "قـيــــــد الإنـــــشـاء" — so the
     * check normalises before comparing.
     */
    public function isUnderConstruction(): bool
    {
        foreach ($this->lines as $line) {
            $key = ArabicText::key($line);

            if (str_contains($key, 'قيد الانشاء') || str_contains(mb_strtolower($line), 'under construction')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Contact details parsed out of "label : value" lines.
     *
     * @return array{emails: list<string>, phones: list<string>}
     */
    public function contactDetails(): array
    {
        $emails = $phones = [];

        foreach ($this->lines as $line) {
            if (preg_match('/[\w.\-]+@[\w.\-]+\.\w+/u', $line, $m)) {
                $emails[] = $m[0];
            }

            if (preg_match('/\b(00\d{9,15}|\+\d{9,15})\b/u', $line, $m)) {
                $phones[] = $m[1];
            }
        }

        return [
            'emails' => array_values(array_unique($emails)),
            'phones' => array_values(array_unique($phones)),
        ];
    }

    /**
     * Group the flat lines into blocks the section builder can consume.
     *
     * Each block is one of:
     *   ['kind' => 'heading',    'text' => string]
     *   ['kind' => 'paragraph',  'text' => string]
     *   ['kind' => 'list',       'items' => list<string>]
     *   ['kind' => 'definitions','items' => list<array{term: string, description: string}>]
     *
     * @return list<array<string, mixed>>
     */
    public function blocks(): array
    {
        return $this->collapseDefinitions($this->rawBlocks());
    }

    /**
     * Collapse the "numbered term, then description" pattern into definition
     * pairs. The memberships page uses it for all six tiers:
     *
     *   1 – الأعضاء المؤسسون
     *   هو صاحب المبادرة في تأسيس المنظمة …
     *
     * Left as separate blocks these render as a broken list of one-item
     * lists; paired, they render as a proper definition set.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function collapseDefinitions(array $blocks): array
    {
        $result = [];
        $index = 0;
        $count = count($blocks);

        while ($index < $count) {
            $pairs = [];
            $cursor = $index;

            // Greedily consume [single-item list, one-or-more paragraphs].
            // A description may run across several paragraphs — tier 3 of the
            // memberships list does exactly that — so keep absorbing them
            // until the next term begins.
            while (
                $cursor + 1 < $count
                && $blocks[$cursor]['kind'] === 'list'
                && count($blocks[$cursor]['items']) === 1
                && $blocks[$cursor + 1]['kind'] === 'paragraph'
            ) {
                $term = $blocks[$cursor]['items'][0];
                $cursor++;

                $description = [];
                while ($cursor < $count && $blocks[$cursor]['kind'] === 'paragraph') {
                    $description[] = $blocks[$cursor]['text'];
                    $cursor++;
                }

                $pairs[] = [
                    'term' => $term,
                    'description' => implode(' ', $description),
                ];
            }

            // Two or more pairs is a deliberate pattern; a single pair is
            // more likely coincidence, so leave it as authored.
            if (count($pairs) >= 2) {
                $result[] = ['kind' => 'definitions', 'items' => $pairs];
                $index = $cursor;

                continue;
            }

            $result[] = $blocks[$index];
            $index++;
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rawBlocks(): array
    {
        $blocks = [];

        /** @var list<string> $pendingList consecutive list items awaiting a flush */
        $pendingList = [];

        foreach ($this->lines as $line) {
            // Skip the bare domain and the construction notice — both are
            // surfaced as structured fields rather than body copy.
            if (preg_match('/^\s*www\.[A-Za-z0-9\-]+\.[A-Za-z]{2,}\s*$/u', $line)) {
                continue;
            }

            $key = ArabicText::key($line);

            if (str_contains($key, 'قيد الانشاء') || str_contains(mb_strtolower($line), 'under construction')) {
                continue;
            }

            if (ArabicText::isUnderscoreHeading($line)) {
                $this->flushList($blocks, $pendingList);
                $text = ArabicText::clean($line);

                if ($text !== '') {
                    $blocks[] = ['kind' => 'heading', 'text' => $text];
                }

                continue;
            }

            if (ArabicText::isListItem($line)) {
                $item = ArabicText::clean(ArabicText::stripListMarker($line));

                if ($item !== '') {
                    $pendingList[] = $item;
                }

                continue;
            }

            $this->flushList($blocks, $pendingList);
            $text = ArabicText::clean($line);

            if ($text !== '') {
                $blocks[] = ['kind' => 'paragraph', 'text' => $text];
            }
        }

        $this->flushList($blocks, $pendingList);

        return $blocks;
    }

    /**
     * Emit any buffered list items as a single list block, then clear the
     * buffer. Written as a method taking the buffer by reference so its
     * mutation stays analyzable (a captured-by-ref closure is not).
     *
     * @param  list<array<string, mixed>>  $blocks
     * @param  list<string>  $pendingList
     */
    private function flushList(array &$blocks, array &$pendingList): void
    {
        if ($pendingList !== []) {
            $blocks[] = ['kind' => 'list', 'items' => $pendingList];
            $pendingList = [];
        }
    }
}
