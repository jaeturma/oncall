<?php

namespace App\Services\Notifications\Fcm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Exchanges the Firebase service-account JSON credential for a short-lived
 * OAuth2 access token (FCM HTTP v1 requires this — there is no more static
 * "server key" like the legacy API). Hand-rolled RS256 JWT signing via
 * `openssl_sign` rather than pulling in a Google API client SDK, mirroring
 * Phase L's decision to call providers directly through `Http` instead of
 * adding a heavy dependency for a single endpoint.
 *
 * Never logs the private key or the resulting bearer token (Phase M Step 35 / 43).
 */
class FcmAccessTokenProvider
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /** Null when Firebase hasn't been configured yet — callers must check {@see isConfigured()} first. */
    public function isConfigured(): bool
    {
        $path = config('services.fcm.credentials_path');

        return filled($path) && filled(config('services.fcm.project_id')) && File::exists($path);
    }

    public function projectId(): string
    {
        return (string) config('services.fcm.project_id');
    }

    public function token(): string
    {
        $credentials = $this->credentials();

        return Cache::remember('fcm-access-token:'.md5($credentials['client_email']), now()->addMinutes(50), function () use ($credentials): string {
            $jwt = $this->buildAssertion($credentials);

            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful() || blank($response->json('access_token'))) {
                throw new RuntimeException('Unable to obtain an FCM access token.');
            }

            return $response->json('access_token');
        });
    }

    /** @return array{client_email: string, private_key: string} */
    private function credentials(): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('FCM credentials are not configured.');
        }

        $json = json_decode(File::get(config('services.fcm.credentials_path')), true);

        if (! is_array($json) || blank($json['client_email'] ?? null) || blank($json['private_key'] ?? null)) {
            throw new RuntimeException('FCM credentials file is malformed.');
        }

        return ['client_email' => $json['client_email'], 'private_key' => $json['private_key']];
    }

    /** @param  array{client_email: string, private_key: string}  $credentials */
    private function buildAssertion(array $credentials): string
    {
        $now = time();
        $segments = [
            $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ])),
        ];

        $signingInput = implode('.', $segments);
        $signed = openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        if (! $signed) {
            throw new RuntimeException('Unable to sign the FCM access-token request.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
