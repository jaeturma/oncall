<?php

namespace App\Http\Requests\Admin;

use App\Services\Notifications\NotificationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-notification-settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'push_enabled' => ['sometimes', 'boolean'],
            'sms_fallback_enabled' => ['sometimes', 'boolean'],
            // Admin can only pick from events the catalog itself marks eligible
            // (Step 33) — this is a bound selection, not a free-text policy.
            'sms_fallback_events' => ['sometimes', 'array'],
            'sms_fallback_events.*' => [Rule::in(NotificationCatalog::smsFallbackEligibleKeys())],
            'retry_max_attempts' => ['required', 'integer', 'between:1,10'],
        ];
    }
}
