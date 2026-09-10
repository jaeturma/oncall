<?php

namespace Database\Factories;

use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderService>
 */
class ProviderServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'service_id' => Service::factory(),
            'experience_text' => fake()->sentence(),
            'active' => true,
        ];
    }
}
