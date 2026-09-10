<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'service_finder_id' => fn (array $attributes): int => ServiceRequest::findOrFail($attributes['service_request_id'])->service_finder_id,
            'provider_id' => fn (array $attributes): int => ServiceRequest::findOrFail($attributes['service_request_id'])->requested_provider_id,
            'agreed_price' => fake()->randomFloat(2, 500, 5000),
            'status' => JobStatus::Accepted,
            'accepted_at' => now(),
        ];
    }
}
