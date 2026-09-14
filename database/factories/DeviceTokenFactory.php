<?php

namespace Database\Factories;

use App\Enums\DevicePlatform;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeviceToken>
 */
class DeviceTokenFactory extends Factory
{
    protected $model = DeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'installation_id' => (string) Str::uuid(),
            'platform' => fake()->randomElement(DevicePlatform::cases()),
            'fcm_token' => Str::random(40),
            'device_name' => fake()->randomElement(['Pixel 8', 'iPhone 15', 'Galaxy S24']),
            'app_version' => '1.0.0',
            'is_active' => true,
            'last_used_at' => now(),
        ];
    }
}
