<?php

namespace App\Services\Sms;

use App\Enums\SmsDeliveryStatus;
use App\Enums\SmsProviderDriver;
use App\Models\SmsDeliveryLog;
use App\Models\SmsProvider;
use App\Models\SmsSetting;
use App\Models\User;
use App\Services\Sms\Providers\GenericHttpSmsProvider;

/**
 * The single entry point application code calls to send an SMS — it never
 * knows or cares which provider is active. Every send attempt is recorded
 * to `sms_delivery_logs` regardless of outcome, and the message body is
 * never persisted there (see the log model's docblock).
 */
class SmsManager
{
    public function send(SmsMessage $message, ?User $user = null): SmsResult
    {
        $settings = SmsSetting::current();

        if (! $settings->enabled) {
            return SmsResult::failure('sms_disabled', 'SMS delivery is currently disabled.', SmsDeliveryStatus::Rejected);
        }

        $provider = $settings->activeProvider;
        if ($provider === null) {
            return SmsResult::failure('no_active_provider', 'No SMS provider is configured.', SmsDeliveryStatus::Rejected);
        }

        $log = SmsDeliveryLog::create([
            'user_id' => $user?->id,
            'mobile' => $message->mobile,
            'provider' => $provider->name,
            'purpose' => $message->purpose?->value,
            'message_type' => $message->messageType,
            'status' => SmsDeliveryStatus::Queued,
            'queued_at' => now(),
        ]);

        $result = $this->resolveDriver($provider)->send($message);

        $log->update([
            'status' => $result->status,
            'provider_message_id' => $result->providerMessageId,
            'sent_at' => $result->successful ? now() : null,
            'failed_at' => $result->successful ? null : now(),
            'error_code' => $result->errorCode,
        ]);

        return $result;
    }

    public function testConnection(SmsProvider $provider, string $testMobile): SmsResult
    {
        return $this->resolveDriver($provider)->testConnection($testMobile);
    }

    private function resolveDriver(SmsProvider $provider): SmsProviderInterface
    {
        return match ($provider->driver) {
            SmsProviderDriver::GenericHttp => new GenericHttpSmsProvider($provider->config ?? []),
        };
    }
}
