<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'label' => ['ar' => 'رابط', 'en' => 'Link'],
            'url' => '/'.$this->faker->slug(1),
            'sort' => 0,
        ];
    }
}
