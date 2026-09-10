<?php

namespace Database\Factories;

use App\Enums\JobMessageType;
use App\Models\Job;
use App\Models\JobMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobMessage>
 */
class JobMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'sender_id' => User::factory(),
            'type' => JobMessageType::Message,
            'body' => fake()->sentence(),
        ];
    }
}
