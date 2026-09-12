# PHASE B — Marketplace vs Back-Office Role Boundaries

Read `docs/architecture/AUTHORIZATION_AUDIT.md` first.

Goal: create a central, maintainable separation between marketplace users and back-office staff.

Marketplace-capable: customer, provider, sponsor/referrer.
Web-only: super_admin, admin, verifier, accounting, budget, cashier, enforcement/compliance, maintenance.

Implement reusable authorization concepts equivalent to:
- `can_use_marketplace`
- `can_use_mobile`
- `can_access_admin`

Prefer account capabilities, roles/permissions, gates, policies, middleware, or model helpers over scattered string comparisons.

A back-office-only account must not automatically gain marketplace abilities. A marketplace account must not access back-office operations.

Add tests proving customer/provider/sponsor are marketplace-capable, admin/accounting are not mobile-capable, and customer is not admin-capable.

Run `php artisan optimize:clear` and `php artisan test`.

Stop when role boundaries are centrally defined and tested.
