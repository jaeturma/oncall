<?php

namespace App\Enums;

enum UserRole: string
{
    case ServiceFinder = 'SERVICE_FINDER';
    case ServiceProvider = 'SERVICE_PROVIDER';
    case Admin = 'ADMIN';
    case Accounting = 'ACCOUNTING';
    case Budget = 'BUDGET';
    case Cashier = 'CASHIER';

    /**
     * Marketplace-capable roles (Phase B / ADR-001): customer and provider.
     * Sponsor/Referrer is deliberately not a role — it's the existing
     * `sponsor_user_id` relationship any marketplace user can hold — so it
     * needs no separate case here.
     */
    public function canUseMarketplace(): bool
    {
        return in_array($this, [self::ServiceFinder, self::ServiceProvider], true);
    }

    /**
     * Whether this role may use the Flutter mobile app. The Flutter client
     * is a marketplace-only surface (see the phase master prompt's MOBILE
     * RULE), so today this is identical to {@see self::canUseMarketplace()}.
     * Kept as its own method rather than an alias so mobile access can
     * diverge from web marketplace access later without redefining that
     * method or touching every call site that only cares about the web
     * marketplace boundary.
     */
    public function canUseMobile(): bool
    {
        return $this->canUseMarketplace();
    }

    /**
     * Whether this role may reach the `/admin` web administration portal.
     * Per ADR-001, Super Admin / Verifier / Enforcement-Compliance /
     * Maintenance all collapse into this single Admin role — there is no
     * separate "super admin" tier.
     */
    public function canAccessAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Whether this role is any kind of back-office/web-only staff account
     * (the broader category), as opposed to a marketplace account. This is
     * the set that may reach the `/staff` finance queues (Accounting,
     * Budget, Cashier) in addition to Admin — narrower staff-only actions
     * (e.g. releasing a specific payment) still use their own, narrower
     * policy checks; this is only the outer "is this a back-office account
     * at all" boundary.
     */
    public function canAccessBackOffice(): bool
    {
        return in_array($this, [self::Admin, self::Accounting, self::Budget, self::Cashier], true);
    }
}
