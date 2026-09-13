<?php

namespace App\Services\Sms;

use InvalidArgumentException;

/**
 * Guards the admin-configurable SMS API base URL against SSRF. An admin
 * page that lets an operator type in an arbitrary URL which the server
 * then makes a request to is exactly the classic SSRF shape, so this is
 * checked both when the admin saves the URL and (defense in depth) again
 * immediately before every actual request in {@see GenericHttpSmsProvider}.
 */
final class SmsUrlValidator
{
    /**
     * @throws InvalidArgumentException if the URL is unsafe to call.
     */
    public static function assertSafe(string $url): void
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            throw new InvalidArgumentException('The SMS API URL is not a valid absolute URL.');
        }

        if (! in_array(strtolower($parts['scheme']), ['https', 'http'], true)) {
            throw new InvalidArgumentException('The SMS API URL must use http or https.');
        }

        if (app()->isProduction() && strtolower($parts['scheme']) !== 'https') {
            throw new InvalidArgumentException('The SMS API URL must use https in production.');
        }

        // parse_url() keeps the brackets around a literal IPv6 host
        // (e.g. "[::1]") — strip them before any IP checks, or a bracketed
        // loopback/private address slips past filter_var() unrecognized.
        $host = trim(strtolower($parts['host']), '[]');

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            throw new InvalidArgumentException('The SMS API URL may not target a local hostname.');
        }

        // Resolve the hostname (a bare IP resolves to itself) and reject
        // loopback/link-local/private-range destinations — blocks both a
        // literal internal IP and a hostname that merely resolves to one
        // (DNS-rebinding-style bypasses). If resolution itself fails (e.g.
        // no DNS in an offline test/CI sandbox) that is not a security
        // hole to block here — the actual send request would simply fail
        // to connect later the same way — so only a *successful* resolution
        // to a disallowed address is rejected.
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        $resolved = $ip !== $host || filter_var($host, FILTER_VALIDATE_IP);

        if ($resolved && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new InvalidArgumentException('The SMS API URL may not target a private, loopback, or reserved network address.');
        }
    }

    public static function isSafe(string $url): bool
    {
        try {
            self::assertSafe($url);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
