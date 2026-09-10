<?php

namespace Database\Factories;

use App\Enums\AppealStatus;
use App\Enums\EnforcementCaseStatus;
use App\Enums\ReportCategory;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnforcementCase>
 */
class EnforcementCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'violation_category' => ReportCategory::Other,
            'severity' => ViolationSeverity::Low,
            'status' => EnforcementCaseStatus::Open,
            'appeal_status' => AppealStatus::None,
        ];
    }
}
