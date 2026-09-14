<?php

namespace App\Services\Notifications\Fcm;

use App\Enums\PushFailureType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around FCM's HTTP v1 `messages:send` endpoint — the only
 * thing in the app that builds a Firebase HTTP request (Phase M Step 11:
 * "Do not duplicate Firebase HTTP logic throughout the application").
 * Every failure is classified into a {@see PushFailureType} so the caller
 * can decide retry vs. deactivate-token vs. give up (Step 42), and nothing
 * here ever logs the bearer token or credential material (Step 35 / 43).
 */
class FcmClient
{
    public function __construct(private readonly FcmAccessTokenProvider $tokens) {}

    /**
     * @param  array<string, string>  $data  String-only key/value data payload — never OTPs, tokens, or document paths (Step 34).
     */
    public function send(string $fcmToken, string $title, string $body, array $data): FcmSendResult
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->tokens->projectId()}/messages:send";

        $response = Http::withToken($this->tokens->token())
            ->timeout(10)
            ->post($url, [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                    'android' => ['priority' => 'high', 'notification' => ['channel_id' => 'oncall_general']],
                    'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                ],
            ]);

        if ($response->successful()) {
            return FcmSendResult::success((string) $response->json('name', ''));
        }

        $errorCode = $this->extractErrorCode($response->json());
        Log::warning('fcm_send_failed', ['status' => $response->status(), 'error_code' => $errorCode]);

        return FcmSendResult::failure($this->classify($response->status(), $errorCode), $errorCode ?? (string) $response->status());
    }

    /** @param  array<string, mixed>|null  $body */
    private function extractErrorCode(?array $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $details = $body['error']['details'] ?? [];
        foreach ($details as $detail) {
            if (isset($detail['errorCode'])) {
                return $detail['errorCode'];
            }
        }

        return $body['error']['status'] ?? null;
    }

    private function classify(int $httpStatus, ?string $errorCode): PushFailureType
    {
        return match ($errorCode) {
            'UNREGISTERED', 'SENDER_ID_MISMATCH' => PushFailureType::InvalidToken,
            'INVALID_ARGUMENT' => PushFailureType::ConfigurationError,
            'QUOTA_EXCEEDED', 'UNAVAILABLE', 'INTERNAL' => PushFailureType::Retryable,
            default => $httpStatus >= 500 ? PushFailureType::Retryable : PushFailureType::Permanent,
        };
    }
}
