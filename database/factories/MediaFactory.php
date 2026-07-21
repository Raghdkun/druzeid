<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'media/'.$this->faker->uuid().'.jpg',
            'kind' => 'image',
            'filename' => $this->faker->word().'.jpg',
            'mime' => 'image/jpeg',
            'size' => $this->faker->numberBetween(10_000, 500_000),
            'width' => 1200,
            'height' => 800,
            'variants' => [],
            'alt' => ['ar' => 'صورة', 'en' => 'An image'],
            'caption' => [],
            'focal_x' => 0.5,
            'focal_y' => 0.5,
            'needs_alt_review' => false,
        ];
    }

    /**
     * A video item: no dimensions guaranteed, described by caption, with an
     * optional poster.
     */
    public function video(): static
    {
        return $this->state([
            'path' => 'media/'.$this->faker->uuid().'.mp4',
            'kind' => 'video',
            'filename' => $this->faker->word().'.mp4',
            'mime' => 'video/mp4',
            'width' => 1280,
            'height' => 720,
            'duration' => $this->faker->numberBetween(5, 300),
            'poster_path' => 'media/'.$this->faker->uuid().'-poster.jpg',
            'alt' => [],
            'needs_alt_review' => false,
        ]);
    }

    public function needsAltReview(): static
    {
        return $this->state(['alt' => [], 'needs_alt_review' => true]);
    }

    public function decorative(): static
    {
        return $this->state(['is_decorative' => true]);
    }
}
