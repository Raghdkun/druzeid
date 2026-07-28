<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(6), '.');

        return [
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'title' => ['ar' => $title, 'en' => $title],
            'excerpt' => ['ar' => $this->faker->sentence(12), 'en' => $this->faker->sentence(12)],
            'body' => [
                'ar' => '<p>'.$this->faker->paragraph().'</p>',
                'en' => '<p>'.$this->faker->paragraph().'</p>',
            ],
            'source_name' => null,
            'source_url' => null,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subDays($this->faker->numberBetween(0, 30)),
            'is_featured' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => Article::STATUS_DRAFT, 'published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
