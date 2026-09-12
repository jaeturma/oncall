# Authorization Audit — Phase A

Date: 2026-09-12
Scope: Laravel 13 project `oncall`. Read-only audit per `oncall-claude-phases/PHASE_A_AUTH_AUDIT.md`. No architecture was introduced or replaced; see "Fixes applied in this phase" for the two minimal, in-scope corrections made.

## 1. Login / session flow

- Standard Laravel session auth, one guard (`web`, session driver, Eloquent `App\Models\User` provider). No API guard, no token guard, no Sanctum guard.
- `Auth\AuthenticatedSessionController::store()` — validates, `Auth::attempt()`, regenerates the session on success, throttled `throttle:6,1` on the route.
- `Auth\LogoutController` — `Auth::logout()`, invalidates session, regenerates CSRF token.
- `Auth\RegisteredUserController::store()` — validates `role` against a hard allow-list (`in:SERVICE_FINDER,SERVICE_PROVIDER'`), converts it through `UserRole::from()`, and builds the `User::create()` array field-by-field (never `create($request->all())`/`create($data)`). A privileged role cannot be injected through this endpoint. Covered by `AuthenticationTest::test_registration_rejects_privileged_role`.
- `Auth\PasswordResetController` — standard broker flow (`Password::sendResetLink` / `Password::reset`), forces a fresh `remember_token` on reset.
- Email verification: `User` implements `MustVerifyEmail`; `Auth\VerifyEmailController` uses the framework's `EmailVerificationRequest` (id/hash/signature all checked) behind `signed` + `throttle:6,1`. The `verified` middleware alias is available but **not applied to any route** — verification is currently informational/trust-badge only, not an access gate. Confirm this is intended before Phase B.
- Mobile OTP verification (`MobileVerificationController`) is a bespoke, non-Sanctum, session-based flow — codes are hashed in cache, logged (no SMS gateway configured), and there is no reuse of this for API auth.
- Session cookies: `http_only` = true, `same_site` = `lax` (both fine). `SESSION_SECURE_COOKIE` is **unset** in `.env`. Recommend setting it explicitly to `true` for the production/HTTPS environment so the flag isn't left to the framework's implicit default.
- CSRF: no exemptions registered in `bootstrap/app.php` (`withMiddleware` only aliases `account.active`); the default `web` group's CSRF verification applies to every route in `routes/web.php` unmodified.

## 2. User / account model

- `App\Models\User` — single table, `role` and `status` stored as plain `string` columns (Eloquent-cast to `App\Enums\UserRole` / `App\Enums\UserStatus`; no DB-level enum/check constraint, so integrity depends on every write path going through Eloquent — true today, worth remembering if raw SQL/imports are ever added).
- **`UserRole`** (6 cases): `SERVICE_FINDER`, `SERVICE_PROVIDER`, `ADMIN`, `ACCOUNTING`, `BUDGET`, `CASHIER`.
- **`UserStatus`** (5 cases): `ACTIVE`, `WARNING`, `UNDER_REVIEW`, `RESTRICTED`, `SUSPENDED`.
- **`RestrictedCapability`** (6 cases): a *third*, finer-grained authorization axis layered on top of role/status — `REQUEST_SERVICE`, `ACCEPT_JOBS`, `MESSAGING`, `CONTACT_REVEAL`, `NEW_BOOKINGS`, `FULL_ACCOUNT_ACCESS`. An `EnforcementCase` can restrict specific capabilities on a user without changing their role or fully suspending them; checked via `User::isCapabilityRestricted()`, called from policies (`ServiceRequestPolicy`, `JobPolicy`, `JobMessagePolicy`, `WithdrawalPolicy`).
- **`AccountType`** is *not* a role. It's a marketplace commercial tier (`registration_fee`, `sponsor_commission_type/value`, `platform_commission_percent`, `requires_identity_verification`) attached to a user via `account_type_id`. Distinct concept from `UserRole`; no overlap/duplication found.
- **Sponsor/Referrer is not a role.** It's a self-referencing relationship: any `SERVICE_FINDER` or `SERVICE_PROVIDER` can be another user's `sponsor_user_id`. There is no dedicated "Sponsor" `UserRole` case, no distinct capability set, and no gate that treats sponsors specially beyond `commissionsEarned()`/`sponsoredUsers()`. **This is a real gap against the target model** (see §6).
- **Gap against the target role model.** The permanent architecture in the master prompt calls for `Super Admin, Admin, Verifier, Accounting, Budget, Cashier, Enforcement/Compliance, Maintenance` on the back office and `Customer, Provider, Sponsor/Referrer` on the marketplace side. Today: identity-verification review, enforcement administration, dispute resolution, audit-log viewing, service-catalog maintenance, and location maintenance are **all** gated to the single `ADMIN` role — there is no `Verifier`, `Enforcement/Compliance`, `Super Admin`, or `Maintenance` role, and no `Sponsor` marketplace role. This is the central decision Phase B has to make.

## 3. Route protection — web (marketplace + admin + staff)

96 routes total, all declared in `routes/web.php` (no `routes/api.php` exists — see §4). No `/admin` or `/staff` route-group-level guard middleware exists; every admin/staff route sits in the same single `Route::middleware(['auth', 'account.active'])` group as ordinary marketplace routes. Authorization on every individual route is instead enforced at the controller/FormRequest level, via three coexisting (all currently effective, but stylistically inconsistent) mechanisms:

| Mechanism | Where used | Example |
|---|---|---|
| Inline `abort_unless($user->role === UserRole::Admin, 403)` | Simple admin-only `index()`-style actions with no injected `FormRequest` | `Admin\UserController`, `Admin\ProviderController`, `Admin\JobController`, `Admin\ReportController`, `Admin\ServiceCatalogController`, `Admin\LocationController`, `Admin\AuditLogController` |
| Inline `Gate::authorize(...)` in the controller method | Simple single-permission checks | `Admin\DisputeController::index/show`, `Admin\CommissionController::index`, `Admin\EnforcementCaseController::index/show`, `Staff\*::index`, `DeclinedServiceRequestController`, `CancelledServiceRequestController`, `DisputeController::withdraw`, `WithdrawalController::cancel` |
| `FormRequest::authorize()` | Actions where the right permission depends on request data (e.g. `decision=approve` vs `reverse`) or where a policy method already encodes the full rule | `Admin\DisputeController::update` → `ResolveDisputeRequest` (`can('review', $dispute)`); `Admin\CommissionController::update` → `UpdateCommissionRequest` (`can($decision, $commission)`); `Admin\EnforcementCaseController::update`, `ResolvedEnforcementCaseController`, `ReviewedEnforcementAppealController` → all via `can('update', $case)`; `Staff\JobPaymentReleaseController::update` → `ReleaseJobPaymentRequest` (`can($decision, $jobPayment)`); `Staff\WithdrawalReviewController::update` → `ReviewWithdrawalRequest` (`can('review', $withdrawal)`); `Admin\VerificationReviewController::update` → `ReviewVerificationRequest`; every marketplace mutating action (`accept/decline/cancel` a service request, job status transitions, job messages, reviews, user reports, disputes, appeals, withdrawals, provider-profile create/update) → a dedicated `FormRequest::authorize()` delegating to a Policy method or an explicit ownership check.

**I traced every one of the 96 routes' controller action (and, where present, its `FormRequest::authorize()`) individually.** Every state-changing route has a real, server-side check — none rely on a hidden button or a Blade `@if`. My first pass flagged 8 admin/staff `update()` actions as apparently unchecked (no `Gate::authorize` visible in the controller body); on inspecting the injected `FormRequest::authorize()` for each, all 8 were already correctly protected there. No live authorization bypass was found in the web app. (This is also reflected in existing tests, e.g. `AdministrationTest::test_non_admin_cannot_access_administration_dashboards`, `JobEarningsTest`, `SafetyEnforcementTest`, `AccountTypeAdminTest`, `SponsorCommissionTest`, `WithdrawalWorkflowTest` all assert 403 for the wrong role/actor on these exact routes.)

**Risk to flag for Phase B (architectural, not a live bug):** because this all lives at the controller/FormRequest layer with no route-group backstop, a *newly added* admin or staff route that omits its check would be silently open to any authenticated user — there is nothing at the routing layer that would catch the omission. Phase B should add a role-checking middleware (e.g. `role:admin` / `role:accounting,budget,cashier`) applied at the `/admin` and `/staff` route-group level as defense-in-depth, without removing the existing, working per-action checks.

**Duplication/consistency note (style only, not a vulnerability):** three different idioms are used for what is conceptually the same job (reject a request from the wrong role). Some models declare their policy via the `#[UsePolicy(...)]` attribute *and* get an explicit, redundant `Gate::policy()` registration in `AppServiceProvider` (`Job`, `JobMessage`, `JobPayment`, `Review`, `ServiceRequest`, `Dispute`); others (`UserReport`, `EnforcementCase`, `AccountType`, `Commission`, `Withdrawal`) only get the explicit registration. Harmless today (attribute + explicit registration to the same class doesn't conflict), but worth picking one convention in Phase B.

## 4. Mobile / API protection

**There is no mobile API layer at all yet.** Confirmed:

- `laravel/sanctum` is **not** in `composer.json` / `composer.lock`.
- `routes/api.php` does not exist; `bootstrap/app.php`'s `withRouting()` only registers `web`, `commands`, and `health` — no `api:` key.
- `config/sanctum.php` does not exist (never published).
- `config/auth.php` defines only the `web` guard; no `sanctum`/`api` guard, no `personal_access_tokens` migration.
- No controller, request, or resource in the codebase references `auth:sanctum`, `Sanctum::`, or API tokens.
- No test exercises an API/token-authenticated request.

Every business rule currently reachable from the web (accept/decline a request, confirm payment, request a withdrawal, submit verification, etc.) lives in a **Policy + FormRequest + Service** stack, not in a controller or a Blade view — e.g. `ServiceRequestService`, `JobPaymentService`, `WithdrawalWorkflow`, `EnforcementService`, `CommissionEngine`, `DisputeService`, `ReviewService`. This is good news for Phase D/E: an API controller can call the *same* services and be authorized by the *same* policies without duplicating any business logic — the domain layer is already API-ready in shape. What's missing is purely the transport layer (Sanctum install, `routes/api.php`, API Resources, mobile-scoped controllers).

## 5. Fixes applied in this phase

None were required. Every hypothesis of a missing check (see §3) was disproven on closer inspection of the relevant `FormRequest`. No other obvious, serious, currently-exploitable vulnerability was found, so per the phase's "only fix an obvious serious vulnerability" instruction, **no code was changed**. One low-severity, non-blocking observation is logged for awareness rather than fixed now: the `sponsor_email` registration field (`exists:users,email`) lets an unauthenticated visitor enumerate which email addresses have an Oncall account, via the validation error. Common, low-severity, and not specific to the role/admin boundary this phase is auditing — left for a general security pass (Phase K) rather than fixed here.

## 6. Recommendation for Phase B (Role Boundaries)

1. **Decide the role model before writing any enforcement code.** ~~Two honest options, not a technical detail to skip past~~ **RESOLVED 2026-09-12: option (b) chosen — the existing 6 roles are kept as-is. See `docs/architecture/ADR-001-role-model.md` for the full decision and how each back-office/marketplace concept in the master prompt maps onto them.** Original options, kept for context:
   - **(a) Extend `UserRole`** with `SUPER_ADMIN`, `VERIFIER`, `ENFORCEMENT`, `MAINTENANCE` (back office) and `SPONSOR` (marketplace) to match the master prompt's target model exactly, then split today's single `ADMIN`-only checks (verification review, enforcement administration, dispute resolution, audit log, catalog/location maintenance) across the new roles.
   - **(b) Keep the current 6 roles** (they've worked fine end-to-end) and treat "Super Admin vs Admin" and "Verifier / Enforcement / Maintenance vs Admin" as a *documented, deliberate* simplification for this MVP, formalized as a short ADR rather than left implicit.
   - Sponsor/Referrer should very likely stay a *relationship*, not a role — a `SERVICE_FINDER` or `SERVICE_PROVIDER` being someone's sponsor doesn't change what they're otherwise allowed to do. Recommend keeping it that way regardless of (a) or (b).
2. **Add a route-group-level role middleware** for `/admin/*` and `/staff/*` as defense-in-depth, without touching the individual working checks already in place.
3. **Pick one policy-registration convention** (attribute or explicit `Gate::policy()`, not both) and apply it consistently.
4. **Decide whether email/mobile verification should gate anything** (currently informational only) before Phase B ties role boundaries to verification state.
5. Phase B does *not* need to touch Sanctum/API — that's D/E/F's job, and per §4 the domain-service layer is already positioned not to need any duplication when that lands.

## 7. Commands run

```
php artisan optimize:clear   → all caches cleared, no errors
php artisan route:list       → 96 routes, all under routes/web.php (no api.php)
php artisan test             → 167 passed, 572 assertions, 0 failures
```

## 8. Files inspected

`app/Models/User.php`, `ProviderProfile.php`, `AccountType.php`, `Job.php`, `JobMessage.php`, `EnforcementCase.php`; `app/Enums/UserRole.php`, `UserStatus.php`, `RestrictedCapability.php`, `VerificationStatus.php`; all 11 files in `app/Policies/`; `app/Http/Middleware/EnsureAccountIsActive.php`; `app/Providers/AppServiceProvider.php`; `routes/web.php`; every controller under `app/Http/Controllers/Auth/`, `app/Http/Controllers/Admin/`, `app/Http/Controllers/Staff/`, `app/Http/Controllers/Provider/`, and the top-level marketplace controllers (`JobController`, `JobStatusController`, `JobMessageController`, `JobPaymentController`, `ReviewController`, `UserReportController`, `DisputeController`, `EnforcementAppealController`, `WithdrawalController`, `NotificationController`, `ServiceRequestController`, `ProviderSearchController`, `ProviderController`); every relevant `app/Http/Requests/*.php` `authorize()` method (24 files); `config/auth.php`, `config/session.php`; `composer.json`/`composer.lock`; `bootstrap/app.php`; migrations for `users`, `account_types`; `tests/Feature/AuthenticationTest.php`, `AdministrationTest.php`, `AccountSuspensionTest.php`, `JobEarningsTest.php`, `SafetyEnforcementTest.php`, `AccountTypeAdminTest.php`, `SponsorCommissionTest.php`, `WithdrawalWorkflowTest.php`.
