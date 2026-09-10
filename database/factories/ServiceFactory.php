<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['service_category_id' => ServiceCategory::factory(), 'name' => Str::title($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999), 'active' => true];
    }
}
