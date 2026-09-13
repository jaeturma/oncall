<?php

namespace App\Models;

use App\Enums\SmsProviderDriver;
use Database\Factories\SmsProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One configured SMS provider. `config` is a single `encrypted:array` cast
 * column, so every driver-specific field (base URL, auth type, credential,
 * sender id, ...) is encrypted at rest without needing a dedicated column
 * per secret. Never serialize `config` to a browser/API response directly —
 * always go through {@see maskedConfig()}.
 */
#[Fillable(['driver', 'name', 'config', 'is_active'])]
class SmsProvider extends Model
{
    /** @use HasFactory<SmsProviderFactory> */
    use HasFactory;

    /** Config keys whose values are secrets and must never be shown in full once saved. */
    public const SECRET_CONFIG_KEYS = ['credential'];

    protected function casts(): array
    {
        return [
            'driver' => SmsProviderDriver::class,
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * `config` with every secret value replaced by a masked display string
     * (e.g. `••••••••4F2A`) — safe to hand to a Blade view or JSON response.
     *
     * @return array<string, mixed>
     */
    public function maskedConfig(): array
    {
        $config = $this->config ?? [];

        foreach (self::SECRET_CONFIG_KEYS as $key) {
            if (! empty($config[$key])) {
                $config[$key] = str_repeat('•', 8).mb_substr((string) $config[$key], -4);
            }
        }

        return $config;
    }
}
