# Web/Mobile Security Boundary — Phase K Sign-Off

Date: 2026-09-13
Scope: final architecture review after Phases A–J. Supersedes nothing; formalizes and cross-references decisions already made in `AUTHORIZATION_AUDIT.md` (Phase A) and `ADR-001-role-model.md`.

## 1. Roles

Six roles (`App\Enums\UserRole`), unchanged since ADR-001:

| Role | Surface | Notes |
|---|---|---|
| `SERVICE_FINDER` | Mobile + web (own account only) | Customer |
| `SERVICE_PROVIDER` | Mobile + web (own account only) | Provider |
| `ADMIN` | Web only (`/admin`) | Collapses Super Admin/Verifier/Enforcement/Maintenance per ADR-001 |
| `ACCOUNTING` | Web only (`/staff`, part of `/admin`) | Cashout stage 1, job-payment release |
| `BUDGET` | Web only (`/staff`) | Cashout stage 2 |
| `CASHIER` | Web only (`/staff`) | Cashout stage 3 |

Sponsor/Referrer is **not a role** — it is the `sponsor_user_id` relationship any `SERVICE_FINDER`/`SERVICE_PROVIDER` can hold. There is no separate marketplace role to onboard or restrict.

Role is set exactly once, at registration, from a hardcoded `in:SERVICE_FINDER,SERVICE_PROVIDER` allow-list on both `Auth\RegisteredUserController` (web) and `Api\V1\Auth\RegisteredUserController` (mobile). No other code path ever writes `users.role` — confirmed by an exhaustive grep of every `'role' =>` assignment in `app/`. A back-office role can only be assigned by direct database access (seeding/console), never through any web or mobile request.

## 2. Central capability model

Four boolean capabilities, defined once on `App\Enums\UserRole` and delegated to by `App\Models\User`, are the single source of truth every other check builds on:

- `canUseMarketplace()` / `canUseMobile()` — `SERVICE_FINDER`, `SERVICE_PROVIDER` only.
- `canAccessAdmin()` — `ADMIN` only.
- `canAccessBackOffice()` — `ADMIN`, `ACCOUNTING`, `BUDGET`, `CASHIER`.

Mirrored as gates (`use-marketplace`, `use-mobile`, `access-admin`, `access-back-office`) in `AppServiceProvider`, used as route-group middleware backstops (§3) *and* re-checked at login (§4) so the boundary holds even if one layer is ever bypassed.

## 3. Route boundaries

137 routes total (`php artisan route:list`, after this review's fix — see §7 finding 1, which removed 2 framework-registered routes), three surfaces, no overlap:

- **`routes/web.php`** — marketplace routes sit in one `['auth', 'account.active']` group; `/admin/*` additionally requires `can:access-admin` (or `can:view-finance-reports` for the Accounting-shared commissions/finance pages); `/staff/*` requires `can:access-back-office`. Verified line-by-line for this report: every route under those two prefixes sits inside one of the three guarded groups — none sit outside them.
- **`routes/api.php`** (`/api/v1`, 42 routes) — every route requires `auth:sanctum` + `account.active` + `can:use-mobile` except `auth/register` and `auth/login`. **No route exists for any back-office action** — not gated-and-rejected, structurally absent: no cashout approval/disbursement, no verification approval, no enforcement action, no user/role management, no account-type/fee/commission config, no audit log access. `RoleSeparationSuiteTest` (Phase J) proves several of these return 404/405 rather than merely 403, meaning the capability doesn't exist over HTTP at all, independent of who's asking.
- Framework-level routes (`/up`) reviewed separately — see §7 finding 1 for the one that was removed entirely.

These three surfaces are also why a route-group middleware backstop matters: it protects a *newly added* route that forgets its own check, not just the routes audited today.

## 4. Login boundary

- **Web**: session guard, `Auth::attempt()`. No role check at login — any role can start a session (an Admin visiting `/login` gets a normal session, then `/admin/*`'s gate decides what they can reach). A suspended account can log in too, so it can reach its own enforcement case.
- **Mobile**: token guard (Sanctum), `Api\V1\Auth\AuthenticatedSessionController`. Deliberately **not** `Auth::attempt()` (stateless, not the `web` session guard). Credentials are checked first; a back-office role then gets an explicit `canUseMobile()` rejection with a controlled message ("...authorized for the Oncall Philippines web administration portal only") even with a fully correct password — the account never reaches token issuance. A suspended marketplace account can still log in (mirrors web), and can still reach `GET /api/v1/enforcement-cases`; every other mobile route then blocks it via `account.active`.
- Every other `/api/v1` route also carries `can:use-mobile` as a second check, in case a token outlives a role change made after issuance (no revoke-on-role-change job exists — see §8).

## 5. Workflows

### Cashout (`WithdrawalWorkflow`)
`Requested → AccountingReview → BudgetApproval → ForDisbursement → Completed` (plus `Rejected`/`Returned`/`Cancelled`). Each stage's actor role comes from `WithdrawalStatus::actingRole()`, checked independently in both `WithdrawalPolicy::review()` (HTTP layer) and `WithdrawalWorkflow::advance()` (service layer, under `lockForUpdate()`), so a stage can't be skipped or replayed even calling the service directly. Money is held via `WalletTransactionType::WithdrawalHold` at request time and only ever moved by posting new ledger entries (`WithdrawalRelease`, `Withdrawal`) — never edited in place — with `hold_transaction_id` linking the withdrawal to its ledger entries for audit. Mobile: create/view/cancel only (`Api\V1\WithdrawalController`); no advance/approve method exists anywhere in that controller.

### Verification (`ProviderDocument` + `VerificationRecord`)
User/mobile: submit (throttled `5,1`), view own status. No approval path exists on either surface for the submitter. Web verifier (`Admin\VerificationReviewController`, Admin-only): review a `Submitted` document, or **revoke** a previously-`Verified` one (Phase H closed a gap here — revocation was previously unreachable). Every review/revoke writes an `AuditLog` row and is required to carry a reason. Badges (`verified_document_types`, `mobile_verified`, `email_verified`) are computed live from `status === Verified` and non-expired, so a revoke immediately removes the badge everywhere it's shown. `private_path` is `#[Hidden]` on the model and excluded from every API Resource; download requires owner-or-admin (§7 finding 1 covers the framework route that could otherwise have bypassed this).

### Enforcement (`EnforcementCase`)
`Warning → AccountReview → TemporaryRestriction → Suspension`, strictly forward-only (`EnforcementService::nextAction()` throws on a skipped or repeated stage). Every stage transition, appeal review, and resolution writes an `AuditLog` row and now requires a recorded reason (Phase K tightened `resolution` from optional to required). Restriction effects are defined once as `RestrictedCapability` cases and checked everywhere through `User::isCapabilityRestricted()`, which treats `FullAccountAccess` as a wildcard over every other capability. Phase I closed two real gaps here: `Withdrawals` and `AvailabilityChanges` had no capability at all, so a *targeted* restriction (short of full suspension) couldn't actually block a cashout request or an availability toggle. Enforcement management is Admin-only; Accounting/Budget/Cashier are back-office but cannot enforce (tested explicitly).

## 6. Audit strategy

`AuditLog` (`actor_id`, `event`, `subject_type`/`subject_id`, `before_json`/`after_json`, `ip_address`, `user_agent`, timestamps) is written for every state-changing action across the four systems above: withdrawal requests/advances/cancellations, verification reviews/revocations, enforcement actions/appeals/resolutions, and safety-report submissions. `Admin\AuditLogController` is the only read surface, Admin-only, filterable by event. No mobile route reads or writes audit logs.

## 7. Findings from this review (fixed)

1. **Critical — public, unauthenticated document serving route.** Laravel 13's default `config/filesystems.php` ships the `local` disk with `'serve' => true`, which auto-registers a framework-level `GET /storage/{path}` (and `PUT .../upload`) route *outside* `routes/web.php` and its middleware groups. Since verification documents are stored on the `local` disk (`storage/app/private/verification-documents/...`), this route would have served any private document to anyone who knew or guessed its path — completely bypassing `VerificationController::download()`'s ownership/admin check, `#[Hidden(['private_path'])]`, and every API Resource exclusion built in Phases D/H/J. Fixed by setting `'serve' => false`; locked in by a test that fails if the flag is ever flipped back (verified by temporarily reverting it and confirming the test catches it).
2. **Missing rate limits.** `POST /register` and `POST /reset-password` (the actual password-update action) had no throttle at all, unlike `/login` and `/forgot-password` sitting right next to them — an unauthenticated abuse vector for account-spam and reset-token brute-forcing. Both now carry `throttle:6,1`, matching their siblings; both mobile equivalents already had this from Phase D. Locked in by two tests (verified against the pre-fix routes to confirm they actually catch the regression).
3. **Latent crash, only reachable with real data.** `SponsoredUserResource` (mobile `/api/v1/sponsor/referrals`) declared a `readonly $user` property with no type — invalid PHP, fatal at construction. Every prior test hit that endpoint with an empty sponsor list, so the constructor was never actually invoked (`Collection::map()` skips its callback on an empty collection); any user who had actually sponsored someone would have gotten a 500 in production. Found and fixed in Phase J while building the consolidated role-separation suite; not a role-boundary issue, but a genuine regression this review's route-level testing caught that unit-level/smoke testing had missed.

No other issues were found in this pass across: mass assignment (no `create($request->all())`/`update($request->all())` anywhere in `app/`), wallet manipulation (every `WalletLedger::post()` call site passes a server-computed amount, never raw request input), duplicate payouts (withdrawal advance and job-payment release/reverse are all `lockForUpdate()` + status-guarded), self-verification (no path exists on either surface), IDOR (every route-model-bound controller/policy checked owns a resource-level check, not just a role check — spot-checked `job-payments.confirm`, `disputes.withdraw`, `provider.profiles.update`, `provider.availability.update`, document download), and stale routes (full `route:list` reviewed; no orphaned or duplicate paths).

## 8. Known limitations / accepted risk

- **No token revocation on role/status change.** If a user's role or status changes after a mobile token was issued, the token stays valid until its next request, where `can:use-mobile`/`account.active` catch it. There is a window between the change and that next request. Not addressed — would require either short-lived tokens or an active-session invalidation job, neither of which this app has built.
- **No "logout all devices" endpoint.** Deliberately deferred in Phase E — Sanctum's per-token model already makes it trivial to add (`$user->tokens()->delete()`) when actually needed.
- **Mobile phone (SMS OTP) verification has no `/api/v1` route.** `MobileVerificationController` (send/confirm) is web-only; the Flutter app can show `mobile_verified` status but cannot itself trigger OTP verification. A functionality gap, not a security one — its absence doesn't expose anything.
- **`verification.documents.download` (web) has no explicit rate limit.** Ownership/admin check (returning 404 uniformly, not 403, so it doesn't even leak existence) makes ID enumeration unproductive, but a determined authenticated attacker could still hammer the endpoint. Low priority given the access check already holds.
- **`RestrictedCapability` is intentionally not exhaustive.** It covers the actions Phase I named explicitly (service requests, accepting jobs, messaging, contact reveal, withdrawals, availability changes) plus a full-lockout wildcard. A newly added mutating action must remember to add its own capability and check it — nothing enforces that structurally today beyond code review.
- **Six-role model (ADR-001) remains a deliberate simplification.** If real separation-of-duties needs ever require a distinct Verifier/Enforcement/Maintenance role instead of folding them into `ADMIN`, that is a new ADR, not a silent enum change.

## 9. Commands run for this report

```
php artisan optimize:clear
php artisan route:list            → 137 routes (down from 139 — see §7 finding 1)
php artisan route:list --path=admin   → 32 routes, all inside a guarded group
php artisan route:list --path=api/v1  → 42 routes, no back-office action among them
php artisan test                  → 228 passed, 796 assertions
flutter analyze                   → 0 errors (14 pre-existing style infos)
flutter test                      → 13 passed
```
