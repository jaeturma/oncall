<?php

namespace App\Services\Sms\Providers;

use App\Enums\SmsAuthType;
use App\Enums\SmsDeliveryStatus;
use App\Services\Sms\SmsMessage;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsResult;
use App\Services\Sms\SmsUrlValidator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * A provider-independent HTTP SMS gateway. The request shape is fixed
 * (JSON POST of `to`/`message`/`sender_id` with one of four supported
 * auth patterns) — this is deliberately not an arbitrary HTTP request
 * builder, since that would let an admin-configured "provider" become an
 * SSRF/RCE-adjacent tool. See {@see SmsUrlValidator} for the URL guard
 * applied both when config is saved and again here before every request.
 *
 * @phpstan-type Config array{base_url: string, auth_type: string, auth_param_name?: ?string, username?: ?string, credential?: ?string, sender_id?: ?string, default_country_code?: ?string}
 */
class GenericHttpSmsProvider implements SmsProviderInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function send(SmsMessage $message): SmsResult
    {
        return $this->request($message->mobile, $message->body);
    }

    public function testConnection(string $testMobile): SmsResult
    {
        return $this->request($testMobile, 'Oncall Philippines SMS test successful.');
    }

    private function request(string $mobile, string $body): SmsResult
    {
        $baseUrl = (string) ($this->config['base_url'] ?? '');

        try {
            SmsUrlValidator::assertSafe($baseUrl);
        } catch (InvalidArgumentException) {
            return SmsResult::failure('invalid_url', 'The SMS provider is misconfigured.', SmsDeliveryStatus::Rejected);
        }

        $payload = [
            'to' => $mobile,
            'message' => $body,
            'sender_id' => $this->config['sender_id'] ?? null,
        ];

        try {
            $response = $this->authenticatedClient()
                ->timeout(10)
                ->connectTimeout(5)
                ->post($baseUrl, array_filter($payload, fn ($value) => $value !== null));

            if ($response->successful()) {
                return SmsResult::success(providerMessageId: $this->extractMessageId($response->json()));
            }

            $this->logSanitizedFailure($response->status(), $response->body());

            return SmsResult::failure((string) $response->status(), 'The SMS provider rejected the request.');
        } catch (Throwable $exception) {
            Log::warning('SMS provider request failed', ['exception' => $exception::class, 'message' => $this->sanitize($exception->getMessage())]);

            return SmsResult::failure('connection_error', 'Could not reach the SMS provider.');
        }
    }

    private function authenticatedClient(): PendingRequest
    {
        $client = Http::asJson()->acceptJson();
        $authType = SmsAuthType::tryFrom((string) ($this->config['auth_type'] ?? ''));
        $credential = (string) ($this->config['credential'] ?? '');

        return match ($authType) {
            SmsAuthType::BearerToken => $client->withToken($credential),
            SmsAuthType::ApiKeyHeader => $client->withHeaders([(string) ($this->config['auth_param_name'] ?? 'X-Api-Key') => $credential]),
            SmsAuthType::BasicAuth => $client->withBasicAuth((string) ($this->config['username'] ?? ''), $credential),
            SmsAuthType::QueryParam => $client->withOptions(['query' => [(string) ($this->config['auth_param_name'] ?? 'api_key') => $credential]]),
            null => $client,
        };
    }

    /** @param  array<string, mixed>|null  $json */
    private function extractMessageId(?array $json): ?string
    {
        foreach (['message_id', 'id', 'messageId'] as $key) {
            if (! empty($json[$key])) {
                return (string) $json[$key];
            }
        }

        return null;
    }

    /** Logs enough to debug a provider outage without ever writing the credential or message body. */
    private function logSanitizedFailure(int $status, string $body): void
    {
        Log::warning('SMS provider rejected request', ['status' => $status, 'body_excerpt' => $this->sanitize(mb_substr($body, 0, 300))]);
    }

    private function sanitize(string $text): string
    {
        $credential = (string) ($this->config['credential'] ?? '');

        return $credential !== '' ? str_replace($credential, '[redacted]', $text) : $text;
    }
}
