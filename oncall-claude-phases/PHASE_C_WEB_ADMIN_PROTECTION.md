# PHASE C — Secure the Laravel Web Administration Portal

Goal: protect all administrative and maintenance functions behind web-only authorization.

Prefer `/admin` for administrative routes, adapting to existing structure.

Web-only areas include user/provider management, verification review, categories/services, account types, registration fees, sponsor commissions, wallet review, accounting, budget, cashier, enforcement, roles/permissions, settings, and audit logs.

Implement/refine admin login, `/admin` route group, admin middleware, granular permissions, session auth, CSRF, unauthorized handling, and logout.

Role examples:
- Accounting: cashouts, wallet/commission review, financial reports
- Budget: budget approval queue
- Cashier: disbursement queue/history
- Verifier: verification queue/history
- Enforcement: incidents/restrictions/suspensions
- Super Admin: all permitted modules

Do not rely on menu hiding.

Tests must prove customers/providers/sponsors cannot access admin and back-office roles cannot perform each other's restricted actions.

Run:
```bash
php artisan route:list --path=admin
php artisan test
```

Stop when admin routes are server-side protected and role-scoped.
