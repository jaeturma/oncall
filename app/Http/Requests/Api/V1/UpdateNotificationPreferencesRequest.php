<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.category' => ['required', Rule::enum(NotificationCategory::class)],
            'preferences.*.push_enabled' => ['sometimes', 'boolean'],
            'preferences.*.sms_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
