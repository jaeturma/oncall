<?php

namespace App\Http\Requests\Admin;

use App\Enums\SmsAuthType;
use App\Enums\SmsProviderDriver;
use App\Rules\SafeSmsTemplate;
use App\Rules\SafeSmsUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the combined SMS settings form (global switch + OTP policy +
 * the active provider's configuration). Bounds on the OTP policy fields
 * are deliberately hard limits, not just UI hints — Phase L is explicit
 * that unsafe values (0-second cooldown, unlimited attempts, day-long
 * expiry) must never be reachable from the admin form.
 */
class UpdateSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-sms-settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],

            'driver' => ['required', Rule::enum(SmsProviderDriver::class)],
            'provider_name' => ['required', 'string', 'max:120'],
            'base_url' => ['required', 'string', 'max:500', 'url', new SafeSmsUrl],
            'auth_type' => ['required', Rule::enum(SmsAuthType::class)],
            'auth_param_name' => ['nullable', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:120'],
            'credential' => ['nullable', 'string', 'max:500'],
            'sender_id' => ['nullable', 'string', 'max:20'],
            'default_country_code' => ['nullable', 'string', 'max:5'],

            'test_mobile_number' => ['nullable', 'string', 'max:20'],
            'otp_length' => ['required', 'integer', 'between:4,8'],
            'otp_expiry_minutes' => ['required', 'integer', 'between:2,15'],
            'otp_resend_cooldown_seconds' => ['required', 'integer', 'between:30,600'],
            'otp_max_attempts' => ['required', 'integer', 'between:3,10'],
            'otp_max_sends_per_mobile_per_hour' => ['required', 'integer', 'between:1,50'],
            'otp_max_sends_per_ip_per_hour' => ['required', 'integer', 'between:1,200'],
            'otp_message_template' => ['required', 'string', 'max:480', new SafeSmsTemplate],
        ];
    }
}
