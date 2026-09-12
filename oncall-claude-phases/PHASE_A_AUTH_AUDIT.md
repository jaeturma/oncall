# PHASE A — Authentication and Authorization Audit

You are working inside the Laravel 13 project `oncall`.

Goal: understand the existing authentication, authorization, account type, role, permission, API, Sanctum, and admin-route implementation before changing architecture.

Permanent architecture:
- Mobile: customer, provider, sponsor/referrer
- Web-only back office: super admin, admin, verifier, accounting, budget, cashier, enforcement/compliance, maintenance

Inspect at minimum: `app/Models/User.php`, related models, middleware, policies, gates, `app/Providers/`, `routes/web.php`, `routes/api.php`, auth/admin/API controllers, migrations, seeders, Sanctum configuration, `config/auth.php`, middleware aliases, and tests.

Search for role/account/permission checks, `auth:sanctum`, `Gate::`, policies, and direct role comparisons.

Create `docs/architecture/AUTHORIZATION_AUDIT.md` documenting current login flows, user/account model, role/account types, admin route protection, mobile/API protection, Sanctum usage, weaknesses, duplication, risks, and the minimal Phase B recommendation.

Do not introduce a second role system if one already exists. Only make minimal safety fixes for an obvious serious vulnerability.

Run:
```bash
php artisan optimize:clear
php artisan route:list
php artisan test
```

Stop after the audit is complete. Report files inspected, architecture found, risks, and exact recommendation for Phase B.
