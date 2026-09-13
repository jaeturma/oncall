<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SmsProviderDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestSmsRequest;
use App\Http\Requests\Admin\UpdateSmsSettingsRequest;
use App\Models\AuditLog;
use App\Models\SmsProvider;
use App\Models\SmsSetting;
use App\Services\Sms\SmsManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SmsSettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-sms-settings');
        $settings = SmsSetting::current();
        $provider = $settings->activeProvider ?? new SmsProvider(['driver' => SmsProviderDriver::GenericHttp, 'config' => []]);

        return view('admin.settings.sms', [
            'settings' => $settings,
            'provider' => $provider,
            'maskedConfig' => $provider->exists ? $provider->maskedConfig() : [],
        ]);
    }

    public function update(UpdateSmsSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('manage-sms-settings');
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $settings = SmsSetting::current();
            $before = ['settings' => $settings->toArray(), 'provider_config_keys' => array_keys($settings->activeProvider?->config ?? [])];

            $provider = $settings->activeProvider ?? new SmsProvider(['driver' => $data['driver']]);
            $config = $provider->exists ? ($provider->config ?? []) : [];

            // Leaving the credential field blank preserves the existing secret
            // rather than overwriting it with an empty value.
            $credential = filled($data['credential'] ?? null) ? $data['credential'] : ($config['credential'] ?? null);

            $provider->fill([
                'driver' => $data['driver'],
                'name' => $data['provider_name'],
                'is_active' => true,
                'config' => [
                    'base_url' => $data['base_url'],
                    'auth_type' => $data['auth_type'],
                    'auth_param_name' => $data['auth_param_name'] ?? null,
                    'username' => $data['username'] ?? null,
                    'credential' => $credential,
                    'sender_id' => $data['sender_id'] ?? null,
                    'default_country_code' => $data['default_country_code'] ?? '63',
                ],
            ])->save();

            $settings->update([
                'enabled' => $request->boolean('enabled'),
                'active_sms_provider_id' => $provider->id,
                'otp_length' => $data['otp_length'],
                'otp_expiry_minutes' => $data['otp_expiry_minutes'],
                'otp_resend_cooldown_seconds' => $data['otp_resend_cooldown_seconds'],
                'otp_max_attempts' => $data['otp_max_attempts'],
                'otp_max_sends_per_mobile_per_hour' => $data['otp_max_sends_per_mobile_per_hour'],
                'otp_max_sends_per_ip_per_hour' => $data['otp_max_sends_per_ip_per_hour'],
                'test_mobile_number' => $data['test_mobile_number'] ?? null,
                'otp_message_template' => $data['otp_message_template'],
            ]);

            // Never record the credential itself — only that settings changed.
            AuditLog::create([
                'actor_id' => $request->user()->id,
                'event' => 'sms_settings.updated',
                'subject_type' => SmsSetting::class,
                'subject_id' => $settings->id,
                'before_json' => $before,
                'after_json' => ['enabled' => $settings->fresh()->enabled, 'provider' => $provider->name, 'driver' => $provider->driver->value],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.settings.sms.edit')->with('status', 'SMS settings saved.');
    }

    public function test(SendTestSmsRequest $request, SmsManager $sms): RedirectResponse
    {
        Gate::authorize('send-test-sms');
        $settings = SmsSetting::current();
        $provider = $settings->activeProvider;

        if (! $provider) {
            return back()->withErrors(['mobile' => 'Configure and save an SMS provider before sending a test message.']);
        }

        $result = $sms->testConnection($provider, $request->string('mobile')->value());

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'event' => 'sms_settings.test_sent',
            'subject_type' => SmsProvider::class,
            'subject_id' => $provider->id,
            'before_json' => null,
            'after_json' => ['successful' => $result->successful, 'status' => $result->status->value],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $result->successful
            ? back()->with('status', 'Test SMS sent successfully.')
            : back()->withErrors(['mobile' => 'SMS provider rejected the request.']);
    }
}
