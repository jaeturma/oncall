<?php

namespace App\Enums;

/**
 * Groups every catalog event (see `config/notifications.php`) for the
 * preferences UI. Whether a category can actually be disabled is decided
 * per-event by the `mandatory` flag in the catalog, not by this enum —
 * Security/Account/Enforcement events all still exist here so they can be
 * displayed (read-only) alongside the categories a user can actually toggle.
 */
enum NotificationCategory: string
{
    case ServiceUpdates = 'SERVICE_UPDATES';
    case Messaging = 'MESSAGING';
    case Wallet = 'WALLET';
    case Verification = 'VERIFICATION';
    case Security = 'SECURITY';
    case Enforcement = 'ENFORCEMENT';
    case Account = 'ACCOUNT';
    case Sponsor = 'SPONSOR';
}
