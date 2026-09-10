<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\JobStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobStatusLog>
 */
class JobStatusLogFactory extends Factory
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
            'from_status' => null,
            'to_status' => JobStatus::Accepted,
            'changed_by' => User::factory(),
        ];
    }
}
