# PHASE D — Secure the Marketplace Mobile API

Goal: make `/api/v1/...` marketplace-only.

Allowed domains: auth, profile, verification submission/status, services, locations, provider search/profile, bookings/service requests, messaging, notifications, wallet view, wallet transactions, cashout request/status, sponsored users, incident reporting.

Forbidden mobile domains: admin user management, roles/permissions, account type/fee/commission configuration, wallet adjustments, verification approval, accounting approval, budget approval, cashier disbursement, enforcement administration, settings, audit logs.

Use Sanctum, API versioning, auth middleware, mobile capability checks, Form Requests where useful, API Resources, consistent JSON errors, and rate limiting.

Never expose private ID paths, admin/accounting notes, internal risk data, enforcement notes, private audit metadata, or sensitive configuration.

Run:
```bash
php artisan route:list --path=api/v1
php artisan test
```

Stop when `/api/v1` is marketplace-only and protected.
