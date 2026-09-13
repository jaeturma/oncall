@php
    $rawConfig = $provider->config ?? [];
@endphp

<x-layouts.admin title="SMS &amp; OTP settings" description="Configure the active SMS provider and OTP policy. Credentials are encrypted at rest and never sent back to the browser.">
    <form class="card card-pad grid max-w-3xl gap-6 sm:p-8" method="POST" action="{{ route('admin.settings.sms.update') }}">
        @csrf @method('PATCH')
        <x-form.errors />

        <x-form.checkbox name="enabled" label="SMS delivery enabled" hint="When off, OTP requests fail with a controlled message instead of attempting delivery." :checked="(bool) old('enabled', $settings->enabled)" boxed />

        <h2 class="h3 mt-2">Provider</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="driver" label="Provider driver">
                @foreach(App\Enums\SmsProviderDriver::cases() as $driver)
                    <option value="{{ $driver->value }}" @selected(old('driver', $provider->driver?->value ?? 'GENERIC_HTTP') === $driver->value)>{{ str($driver->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="provider_name" label="Provider display name" :value="old('provider_name', $provider->name)" maxlength="120" required />
        </div>

        <x-form.input name="base_url" label="API base URL" :value="old('base_url', $rawConfig['base_url'] ?? '')" maxlength="500" required hint="Must be a public HTTPS endpoint — internal/private addresses are rejected." />

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="auth_type" label="Authentication">
                @foreach(App\Enums\SmsAuthType::cases() as $authType)
                    <option value="{{ $authType->value }}" @selected(old('auth_type', $rawConfig['auth_type'] ?? 'BEARER_TOKEN') === $authType->value)>{{ str($authType->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="auth_param_name" label="Header / param name (if applicable)" :value="old('auth_param_name', $rawConfig['auth_param_name'] ?? '')" maxlength="120" optional />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="username" label="Username (basic auth only)" :value="old('username', $rawConfig['username'] ?? '')" maxlength="120" optional />
            <x-form.input name="credential" type="password" label="API key / token / password" placeholder="{{ $maskedConfig['credential'] ?? 'Not set' }}" autocomplete="new-password" hint="Leave blank to keep the current value." />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="sender_id" label="Sender ID" :value="old('sender_id', $rawConfig['sender_id'] ?? '')" maxlength="20" optional />
            <x-form.input name="default_country_code" label="Default country code" :value="old('default_country_code', $rawConfig['default_country_code'] ?? '63')" maxlength="5" optional />
        </div>

        <h2 class="h3 mt-2">OTP policy</h2>
        <div class="grid gap-6 sm:grid-cols-3">
            <x-form.input name="otp_length" type="number" label="Code length" min="4" max="8" :value="old('otp_length', $settings->otp_length)" required />
            <x-form.input name="otp_expiry_minutes" type="number" label="Expiry (minutes)" min="2" max="15" :value="old('otp_expiry_minutes', $settings->otp_expiry_minutes)" required />
            <x-form.input name="otp_resend_cooldown_seconds" type="number" label="Resend cooldown (seconds)" min="30" max="600" :value="old('otp_resend_cooldown_seconds', $settings->otp_resend_cooldown_seconds)" required />
            <x-form.input name="otp_max_attempts" type="number" label="Max verify attempts" min="3" max="10" :value="old('otp_max_attempts', $settings->otp_max_attempts)" required />
            <x-form.input name="otp_max_sends_per_mobile_per_hour" type="number" label="Max sends / mobile / hour" min="1" max="50" :value="old('otp_max_sends_per_mobile_per_hour', $settings->otp_max_sends_per_mobile_per_hour)" required />
            <x-form.input name="otp_max_sends_per_ip_per_hour" type="number" label="Max sends / IP / hour" min="1" max="200" :value="old('otp_max_sends_per_ip_per_hour', $settings->otp_max_sends_per_ip_per_hour)" required />
        </div>

        <x-form.textarea name="otp_message_template" label="OTP message template" rows="3" :value="old('otp_message_template', $settings->otp_message_template)" hint="Only @{{otp}}, @{{minutes}}, and @{{app_name}} are supported." required />

        <div class="flex flex-wrap gap-3 border-t border-line pt-6">
            <x-ui.button variant="primary" data-loading-text="Saving…">Save settings</x-ui.button>
        </div>
    </form>

    <div class="card card-pad mt-6 grid max-w-3xl gap-4 sm:p-8">
        <h2 class="h3">Send a test SMS</h2>
        <p class="text-sm text-ink-secondary">Sends "Oncall Philippines SMS test successful." using the currently saved (not unsaved-form) configuration.</p>
        <form class="flex flex-wrap items-end gap-3" method="POST" action="{{ route('admin.settings.sms.test') }}">
            @csrf
            <x-form.input name="mobile" label="Test mobile number" :value="$settings->test_mobile_number" maxlength="20" wrapper-class="grow" required />
            <x-ui.button variant="secondary" data-loading-text="Sending…">Send test SMS</x-ui.button>
        </form>
    </div>
</x-layouts.admin>
