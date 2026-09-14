<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationTemplateRequest;
use App\Models\AuditLog;
use App\Models\NotificationTemplate;
use App\Services\Notifications\NotificationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-notification-templates');

        $overrides = NotificationTemplate::query()->get()->keyBy('event_key');
        $events = collect(NotificationCatalog::events())->map(fn (array $event, string $key) => [
            'key' => $key,
            'category' => $event['category']->value,
            'mandatory' => $event['mandatory'],
            'has_override' => $overrides->has($key),
            'enabled' => $overrides->get($key)?->enabled ?? true,
        ])->values();

        return view('admin.settings.notification-templates.index', ['events' => $events]);
    }

    public function edit(string $event_key): View
    {
        Gate::authorize('manage-notification-templates');
        abort_unless(NotificationCatalog::exists($event_key), 404);

        $event = NotificationCatalog::event($event_key);
        $template = NotificationTemplate::query()->where('event_key', $event_key)->first() ?? new NotificationTemplate(['event_key' => $event_key, 'enabled' => true]);

        return view('admin.settings.notification-templates.edit', [
            'eventKey' => $event_key,
            'event' => $event,
            'template' => $template,
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, string $event_key): RedirectResponse
    {
        abort_unless(NotificationCatalog::exists($event_key), 404);
        $data = $request->validated();

        $template = NotificationTemplate::query()->updateOrCreate(
            ['event_key' => $event_key],
            [
                'enabled' => $request->boolean('enabled'),
                'push_title' => $data['push_title'] ?? null,
                'push_body' => $data['push_body'] ?? null,
                'database_title' => $data['database_title'] ?? null,
                'database_body' => $data['database_body'] ?? null,
            ],
        );

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'event' => 'notification_template.updated',
            'subject_type' => NotificationTemplate::class,
            'subject_id' => $template->id,
            'before_json' => null,
            'after_json' => ['event_key' => $event_key, 'enabled' => $template->enabled],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.settings.notification-templates.edit', $event_key)->with('status', 'Template saved.');
    }
}
