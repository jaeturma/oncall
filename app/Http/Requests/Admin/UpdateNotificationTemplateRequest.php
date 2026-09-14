<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafeNotificationTemplate;
use App\Services\Notifications\NotificationCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-notification-templates') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $eventKey = (string) $this->route('event_key');
        abort_unless(NotificationCatalog::exists($eventKey), 404);

        $placeholders = NotificationCatalog::placeholders($eventKey);
        $rule = new SafeNotificationTemplate($placeholders);

        return [
            'enabled' => ['sometimes', 'boolean'],
            'push_title' => ['nullable', 'string', 'max:120', $rule],
            'push_body' => ['nullable', 'string', 'max:240', $rule],
            'database_title' => ['nullable', 'string', 'max:160', $rule],
            'database_body' => ['nullable', 'string', 'max:2000', $rule],
        ];
    }
}
