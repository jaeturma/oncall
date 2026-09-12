<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
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
            // Only one of available_now / availability_status is set here on
            // purpose: ProviderProfile derives whichever is missing from the
            // one that's present (see ProviderProfile::booted()). Leaving
            // both out of the base definition lets a caller override either
            // field alone (older tests use available_now; the available()/
            // busy()/byAppointment()/offline() states below set both at once
            // with consistent values) without the two fighting over which
            // one "wins" on a brand-new, not-yet-persisted model.
            'available_now' => false,
            'service_radius_km' => 25,
            'credentials_metadata' => ['TESDA certificate'],
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes): array => ['availability_status' => AvailabilityStatus::Available, 'available_now' => true]);
    }

    public function busy(): static
    {
        return $this->state(fn (array $attributes): array => ['availability_status' => AvailabilityStatus::Busy, 'available_now' => false]);
    }

    public function byAppointment(): static
    {
        return $this->state(fn (array $attributes): array => ['availability_status' => AvailabilityStatus::ByAppointment, 'available_now' => false]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes): array => ['availability_status' => AvailabilityStatus::Offline, 'available_now' => false]);
    }
}
