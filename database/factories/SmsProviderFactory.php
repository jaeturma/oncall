<?php

namespace Database\Factories;

use App\Enums\SmsAuthType;
use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsProvider>
 */
class SmsProviderFactory extends Factory
{
    protected $model = SmsProvider::class;

    public function definition(): array
    {
        return [
            'driver' => SmsProviderDriver::GenericHttp,
            'name' => 'Test SMS Gateway',
            'config' => [
                'base_url' => 'https://sms.example.com/send',
                'auth_type' => SmsAuthType::BearerToken->value,
                'auth_param_name' => null,
                'username' => null,
                'credential' => 'test-secret-token-A92F',
                'sender_id' => 'ONCALL',
                'default_country_code' => '63',
            ],
            'is_active' => false,
        ];
    }
}
