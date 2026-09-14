<?php

namespace App\Services\Notifications;

use App\Enums\NotificationCategory;
use InvalidArgumentException;

/**
 * Single source of truth for every notification event (`config/notifications.php`).
 * Business code never invents an event key or a navigation target inline —
 * it dispatches against a key defined here, and any `target` it supplies is
 * validated against the same whitelist Flutter independently re-checks
 * (Phase M Step 4 / Step 26).
 */
class NotificationCatalog
{
    /** @return array<string, array{category: NotificationCategory, mandatory: bool, sms_fallback: bool, placeholders: list<string>, default_title: string, default_body: string}> */
    public static function events(): array
    {
        return config('notifications.events', []);
    }

    /** @return array{category: NotificationCategory, mandatory: bool, sms_fallback: bool, placeholders: list<string>, default_title: string, default_body: string} */
    public static function event(string $key): array
    {
        $event = self::events()[$key] ?? null;

        if ($event === null) {
            throw new InvalidArgumentException("Unknown notification event key: {$key}");
        }

        return $event;
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::events());
    }

    public static function category(string $key): NotificationCategory
    {
        return self::event($key)['category'];
    }

    public static function isMandatory(string $key): bool
    {
        return self::event($key)['mandatory'];
    }

    public static function isSmsFallbackEligible(string $key): bool
    {
        return self::event($key)['sms_fallback'];
    }

    /** @return list<string> */
    public static function placeholders(string $key): array
    {
        return self::event($key)['placeholders'];
    }

    /** @return list<string> Every event key an admin is allowed to enable for SMS fallback. */
    public static function smsFallbackEligibleKeys(): array
    {
        return array_values(array_keys(array_filter(
            self::events(),
            fn (array $event): bool => $event['sms_fallback'],
        )));
    }

    /** @return array<string, bool> screen => whether it requires an id */
    public static function screens(): array
    {
        return config('notifications.screens', []);
    }

    /**
     * Validate a caller-supplied navigation target against the whitelist.
     * Returns null (no target) rather than throwing when the input is
     * missing or malformed — a notification without a safe target still
     * degrades to "open Notifications", it never falls back to a raw URL.
     *
     * @param  array{screen?: string, id?: int|string}|null  $target
     * @return array{screen: string, id?: int|string}|null
     */
    public static function resolveTarget(?array $target): ?array
    {
        if ($target === null || ! isset($target['screen'])) {
            return null;
        }

        $screens = self::screens();
        $screen = $target['screen'];

        if (! array_key_exists($screen, $screens)) {
            return null;
        }

        $requiresId = $screens[$screen];
        $id = $target['id'] ?? null;

        if ($requiresId && ($id === null || $id === '')) {
            return null;
        }

        return $requiresId ? ['screen' => $screen, 'id' => $id] : ['screen' => $screen];
    }
}
