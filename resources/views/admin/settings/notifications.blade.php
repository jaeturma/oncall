<x-layouts.admin title="Notification settings" description="Configure push delivery and SMS fallback for critical events. Firebase server credentials are configured via server environment only and never shown here.">
    <form class="card card-pad grid max-w-3xl gap-6 sm:p-8" method="POST" action="{{ route('admin.settings.notifications.update') }}">
        @csrf @method('PATCH')
        <x-form.errors />

        <x-form.checkbox name="push_enabled" label="Push notifications enabled" hint="When off, every event still creates an in-app notification — only the Firebase push send is skipped." :checked="(bool) old('push_enabled', $settings->push_enabled)" boxed />

        @if(! $fcmConfigured)
            <p class="text-sm text-amber-600">No Firebase credentials are configured on this server yet — push sends will be recorded as skipped until FCM_PROJECT_ID and FCM_CREDENTIALS_PATH are set. See docs/architecture/NOTIFICATION_ARCHITECTURE.md.</p>
        @endif

        <h2 class="h3 mt-2">SMS fallback</h2>
        <x-form.checkbox name="sms_fallback_enabled" label="SMS fallback enabled" hint="Sends an SMS (via the Phase L SMS provider) for the specific critical events checked below — never for every push." :checked="(bool) old('sms_fallback_enabled', $settings->sms_fallback_enabled)" boxed />

        <div class="grid gap-2">
            @foreach($smsFallbackEligible as $eventKey)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="sms_fallback_events[]" value="{{ $eventKey }}" @checked(in_array($eventKey, old('sms_fallback_events', $settings->sms_fallback_events ?? []), true))>
                    {{ str($eventKey)->replace('_', ' ')->title() }}
                </label>
            @endforeach
        </div>

        <x-form.input name="retry_max_attempts" type="number" label="Push retry attempts" min="1" max="10" :value="old('retry_max_attempts', $settings->retry_max_attempts)" required />

        <div class="flex flex-wrap gap-3 border-t border-line pt-6">
            <x-ui.button variant="primary" data-loading-text="Saving…">Save settings</x-ui.button>
        </div>
    </form>

    <div class="card card-pad mt-6 max-w-3xl sm:p-8">
        <p class="text-sm text-ink-secondary">
            Per-event push/in-app text is managed on the
            <a class="link" href="{{ route('admin.settings.notification-templates.index') }}">notification templates</a> page.
            Delivery history is on the <a class="link" href="{{ route('admin.notifications.logs.index') }}">notification logs</a> page.
        </p>
    </div>
</x-layouts.admin>
