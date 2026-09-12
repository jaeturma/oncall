# ADR-001 — Keep the existing 6-role model

Status: **Accepted** (2026-09-12), per explicit decision following the Phase A audit (`docs/architecture/AUTHORIZATION_AUDIT.md`, §6).

## Decision

`App\Enums\UserRole` stays exactly as it is — six cases, no additions:

`SERVICE_FINDER`, `SERVICE_PROVIDER`, `ADMIN`, `ACCOUNTING`, `BUDGET`, `CASHIER`.

The master prompt's target model (`Super Admin, Admin, Verifier, Accounting, Budget, Cashier, Enforcement/Compliance, Maintenance` on the back office; `Customer, Provider, Sponsor/Referrer` on the marketplace side) is **not** being implemented as separate roles. This was option (b) from the audit, chosen over extending the enum.

## How the target concepts map onto the existing roles

| Master-prompt concept | Maps to | Notes |
|---|---|---|
| Super Administrator | `ADMIN` | No separate "super" tier. Every `ADMIN` has the full back-office surface this codebase currently grants Admin (verification review, enforcement, disputes, catalog/location maintenance, audit log, account types, commissions, finance reports). |
| Administrator | `ADMIN` | Same as above — collapsed with Super Admin. |
| Verification Staff | `ADMIN` | `Admin\VerificationReviewController` and `ReviewVerificationRequest` both check `role === UserRole::Admin`. No separate reviewer role. |
| Enforcement / Compliance | `ADMIN` | `Admin\EnforcementCaseController`, `ResolvedEnforcementCaseController`, `ReviewedEnforcementAppealController`, `Admin\DisputeController` all gate on `ADMIN` (directly or via policy `update`/`review`). |
| Maintenance / System Configuration / Role-Permission Management | `ADMIN` | `Admin\ServiceCatalogController`, `Admin\LocationController`, `Admin\AccountTypeController` all gate on `ADMIN`. There is no separate maintenance role and none is planned. |
| Accounting | `ACCOUNTING` | Unchanged — already a distinct role. |
| Budget | `BUDGET` | Unchanged — already a distinct role. |
| Cashier | `CASHIER` | Unchanged — already a distinct role. |
| Sponsor / Referrer | **Not a role.** Stays the existing `sponsor_user_id` relationship. | Any `SERVICE_FINDER` or `SERVICE_PROVIDER` can be another user's sponsor without a role change or a separate marketplace permission set. Being a sponsor changes nothing about what a user is otherwise allowed to do. |
| Customer / Provider | `SERVICE_FINDER` / `SERVICE_PROVIDER` | Unchanged. |

## Rationale

- The 6-role model already has full, working, tested authorization coverage across all 96 web routes (confirmed in the Phase A audit) — splitting `ADMIN` into four roles would touch every one of those checks for no functional gain at this stage.
- The master prompt's own guidance says not to introduce a second role system or duplicate architecture where one already works; it asks for improvement/securing of what exists over wholesale replacement.
- Every back-office capability the split roles would represent (verification, enforcement, maintenance, super-admin) is already fully implemented and enforced under `ADMIN` — the finer separation is a future access-control refinement, not something blocking Phase B or later phases.

## Consequences for later phases

- Phase B's job is to **harden the boundary between the existing 6 roles**, not to introduce new ones: marketplace roles (`SERVICE_FINDER`, `SERVICE_PROVIDER`) must never reach `/admin/*` or `/staff/*`, and the back-office/staff roles (`ADMIN`, `ACCOUNTING`, `BUDGET`, `CASHIER`) must never gain marketplace mobile access by default.
- The audit's route-group-middleware recommendation for `/admin/*` and `/staff/*` (defense in depth, on top of the existing per-action checks) still stands and is unaffected by this decision.
- If a genuine need for separated Verifier/Enforcement/Maintenance/Super-Admin roles emerges later (e.g. real separation-of-duties requirements), that would be a new ADR superseding this one — not a silent enum change.
- Sponsor/Referrer being a relationship rather than a role means Phase F (Flutter marketplace-only) should treat "acting as a sponsor" as something a Customer or Provider account does, not a fourth marketplace role to design screens around.
