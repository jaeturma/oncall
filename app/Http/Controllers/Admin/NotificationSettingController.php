<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationSettingsRequest;
use App\Models\AuditLog;
use App\Models\NotificationSetting;
use App\Services\Notifications\Fcm\FcmAccessTokenProvider;
use App\Services\Notifications\NotificationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotificationSettingController extends Controller
{
    public function edit(FcmAccessTokenProvider $fcm): View
    {
        Gate::authorize('manage-notification-settings');

        return view('admin.settings.notifications', [
            'settings' => NotificationSetting::current(),
            'fcmConfigured' => $fcm->isConfigured(),
            'smsFallbackEligible' => NotificationCatalog::smsFallbackEligibleKeys(),
        ]);
    }

    public function update(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $settings = NotificationSetting::current();
        $before = $settings->toArray();

        $settings->update([
            'push_enabled' => $request->boolean('push_enabled'),
            'sms_fallback_enabled' => $request->boolean('sms_fallback_enabled'),
            'sms_fallback_events' => $data['sms_fallback_events'] ?? [],
            'retry_max_attempts' => $data['retry_max_attempts'],
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'event' => 'notification_settings.updated',
            'subject_type' => NotificationSetting::class,
            'subject_id' => $settings->id,
            'before_json' => $before,
            'after_json' => $settings->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.settings.notifications.edit')->with('status', 'Notification settings saved.');
    }
}
