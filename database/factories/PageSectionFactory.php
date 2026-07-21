<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageSection>
 */
class PageSectionFactory extends Factory
{
    protected $model = PageSection::class;

    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'type' => 'rich_text',
            'payload' => [
                'blocks' => [
                    ['kind' => 'paragraph', 'text' => ['ar' => 'فقرة تجريبية', 'en' => 'A test paragraph']],
                ],
            ],
            'sort' => 0,
            'is_visible' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ofType(string $type, array $payload): static
    {
        return $this->state(['type' => $type, 'payload' => $payload]);
    }
}
