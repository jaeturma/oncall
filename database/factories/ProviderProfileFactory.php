<?php

namespace Database\Factories;

use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderProfile>
 */
class ProviderProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->serviceProvider(),
            'province_id' => Province::factory(),
            'municipality_id' => function (array $attributes): int {
                return Municipality::factory()->create(['province_id' => $attributes['province_id']])->id;
            },
            'bio' => fake()->paragraph(),
            'available_now' => false,
            'service_radius_km' => 25,
            'credentials_metadata' => ['TESDA certificate'],
        ];
    }
}
