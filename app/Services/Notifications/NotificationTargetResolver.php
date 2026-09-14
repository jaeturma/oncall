<?php

namespace App\Services\Notifications;

/**
 * Web-only counterpart to Flutter's route whitelist (`router.dart`). Turns a
 * notification's structured `target` into a real `route()` URL for the
 * Blade notification list — safe here specifically because it is Laravel
 * itself building the URL from a closed set of named routes, never
 * forwarding a raw path/URL taken from notification data (that would be
 * exactly what Phase M Step 4 forbids).
 *
 * @param  array{screen: string, id?: int|string}|null  $target
 */
class NotificationTargetResolver
{
    public function toUrl(?array $target): ?string
    {
        if ($target === null) {
            return null;
        }

        return match ($target['screen']) {
            'service_request' => route('service-requests.show', $target['id']),
            'job', 'conversation' => route('jobs.show', $target['id']),
            'wallet' => route('wallet.index'),
            'withdrawal' => route('withdrawals.index'),
            'verification' => route('verification.index'),
            'notifications' => route('notifications.index'),
            'sponsored_users' => route('sponsor.referrals'),
            'account_status', 'enforcement_case' => isset($target['id'])
                ? route('enforcement-cases.show', $target['id'])
                : route('enforcement-cases.index'),
            default => null,
        };
    }
}
