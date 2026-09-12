# PHASE K — Full Regression, Security Review, and Architecture Sign-Off

Goal: validate the final boundary after Phases A-J.

Review auth flows, `/admin`, `/api/v1`, middleware, policies, permissions, account capabilities, Sanctum tokens, API Resources, cashout, verification, enforcement, audit logs, and Flutter navigation/services if present.

Check for admin endpoints exposed to mobile, UI-only security, mass-assignment issues, private JSON fields, document URL leaks, workflow stage skipping, privilege escalation, IDOR, wallet manipulation, duplicate payouts, self-verification, suspension bypass, missing rate limits, and stale routes.

Run:
```bash
php artisan optimize:clear
php artisan route:list
php artisan route:list --path=admin
php artisan route:list --path=api/v1
php artisan test
```

If Flutter exists:
```bash
flutter analyze
flutter test
```

Create/update `docs/architecture/WEB_MOBILE_SECURITY_BOUNDARY.md` documenting final mobile roles, web-only roles, login boundaries, route boundaries, permission boundaries, workflows, audit strategy, and known limitations.

Final report: architecture implemented, important files changed, tests passed, remaining risks, next recommended phase.

Stop only when the web/mobile boundary is verified and documented.
