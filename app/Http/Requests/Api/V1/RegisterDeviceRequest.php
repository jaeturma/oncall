<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DevicePlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `user_id` is deliberately not a field here at all — the recipient is
 * always `$request->user()` (Phase M Step 7: "Never trust `user_id` from
 * Flutter"). `installation_id` must be an app-generated UUID, never a
 * hardware identifier (Step 6).
 */
class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'platform' => ['required', Rule::enum(DevicePlatform::class)],
            'fcm_token' => ['required', 'string', 'max:512'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ];
    }
}
