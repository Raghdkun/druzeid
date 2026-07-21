<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(3);

        return [
            'slug' => $slug,
            'template' => 'default',
            'title' => ['ar' => 'عنوان تجريبي', 'en' => 'Test Title'],
            'excerpt' => ['ar' => 'مقتطف', 'en' => 'Excerpt'],
            'accent' => 'blue',
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
            'sort' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => Page::STATUS_DRAFT, 'published_at' => null]);
    }

    public function home(): static
    {
        return $this->state([
            'template' => 'home',
            'slug' => 'org-home',
            'title' => ['ar' => 'الرئيسية', 'en' => 'Home'],
        ]);
    }
}
