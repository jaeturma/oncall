<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationRecord>
 */
class VerificationRecordFactory extends Factory
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
            'type' => 'IDENTITY',
            'status' => VerificationStatus::Submitted,
        ];
    }
}
