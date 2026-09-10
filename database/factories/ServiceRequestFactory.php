<?php

namespace Database\Factories;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_finder_id' => User::factory(),
            'requested_provider_id' => User::factory()->serviceProvider(),
            'service_id' => Service::factory(),
            'province_id' => Province::factory(),
            'municipality_id' => function (array $attributes): int {
                return Municipality::factory()->create(['province_id' => $attributes['province_id']])->id;
            },
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'urgency' => ServiceUrgency::SameDay,
            'status' => ServiceRequestStatus::Requested,
        ];
    }
}
