<?php

namespace Database\Factories;

use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Municipality>
 */
class MunicipalityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'name' => fake()->unique()->city(),
            'type' => 'municipality',
        ];
    }
}
