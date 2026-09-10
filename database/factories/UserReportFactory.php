<?php

namespace Database\Factories;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserReport>
 */
class UserReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reported_user_id' => User::factory(),
            'category' => ReportCategory::Other,
            'description' => fake()->paragraph(),
            'status' => ReportStatus::Submitted,
        ];
    }
}
