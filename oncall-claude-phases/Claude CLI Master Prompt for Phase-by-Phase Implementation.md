You are working inside the main Laravel 13 project:

`oncall`

A set of implementation phase instructions has been prepared for this project.

The phase files are located in:

`oncall-claude-phases/`

The files are:

- `PHASE_A_AUTH_AUDIT.md`
- `PHASE_B_ROLE_BOUNDARIES.md`
- `PHASE_C_WEB_ADMIN_PROTECTION.md`
- `PHASE_D_MOBILE_API_PROTECTION.md`
- `PHASE_E_MOBILE_ROLE_ENFORCEMENT.md`
- `PHASE_F_FLUTTER_MARKETPLACE_ONLY.md`
- `PHASE_G_CASHOUT_WORKFLOW.md`
- `PHASE_H_VERIFICATION_WORKFLOW.md`
- `PHASE_I_ENFORCEMENT_WORKFLOW.md`
- `PHASE_J_AUTHORIZATION_TESTS.md`
- `PHASE_K_REGRESSION_AND_SECURITY.md`

You must implement these phases ONE AT A TIME.

Do not skip ahead.
Do not combine multiple phases unless I explicitly instruct you to do so.

# PERMANENT SYSTEM ARCHITECTURE

Oncall Philippines uses:

- Laravel 13 backend
- Laravel Blade web application
- Laravel Blade web administration
- Flutter mobile application for Android and iOS
- Laravel Sanctum for mobile API authentication
- one authoritative Laravel backend/database

The mobile application is for marketplace users only:

- Customer
- Service Provider
- Sponsor / Referrer

The mobile application must NOT contain administrative or back-office functionality.

The following roles and operations are WEB ADMIN ONLY:

- Super Administrator
- Administrator
- Verification Staff
- Accounting
- Budget
- Cashier
- Enforcement / Compliance
- Maintenance
- Role/Permission Management
- System Configuration

This boundary must be enforced server-side.

Never rely only on:
- hidden menu items
- hidden Flutter screens
- Blade conditionals

Laravel authorization must reject unauthorized access.

# MOBILE RULE

Flutter is a marketplace client only.

Do not build or expose mobile functionality for:

- admin dashboard
- accounting approval
- budget approval
- cashier disbursement
- verification approval
- enforcement administration
- role management
- system settings
- account-type maintenance
- commission configuration
- service/category maintenance

# WEB ADMIN RULE

All back-office processing must remain in the secured Laravel web administration portal.

This includes:

- verification review
- account administration
- accounting
- budget approval
- cashier/disbursement
- enforcement
- configuration
- maintenance
- audit functions

# IMPORTANT DEVELOPMENT RULES

Before changing code:

1. Read the requested phase file completely.
2. Inspect the existing project implementation related to that phase.
3. Understand existing migrations, models, controllers, middleware, routes, policies, tests, and services.
4. Reuse working architecture where practical.
5. Do not create duplicate systems.

Do not blindly implement the phase document if equivalent functionality already exists.

Instead:
- inspect it
- improve it
- secure it
- refactor only where necessary

# PRESERVE EXISTING WORK

Do not delete or rewrite unrelated functionality.

Do not introduce breaking architectural changes merely for stylistic preference.

Do not upgrade dependencies unnecessarily.

Do not introduce microservices.

Do not introduce separate databases.

Do not introduce OAuth servers unless the existing project genuinely requires one.

Keep the MVP maintainable.

# BUSINESS LOGIC

Important business logic must not live only inside Blade views or route closures.

Prefer reusable services/actions for important domain operations.

Examples:

- booking/service requests
- verification
- wallet
- commissions
- withdrawals
- enforcement
- provider availability

Web controllers and API controllers may call the same underlying business logic.

Do not duplicate business rules between web and mobile.

Laravel is the source of truth.

# SECURITY RULE

Assume the Flutter application can be reverse-engineered.

Therefore:

- do not trust client-supplied role information
- do not trust hidden UI
- do not trust client-side validation alone
- do not expose private fields unnecessarily
- authorize all sensitive actions server-side

Use Laravel:
- middleware
- policies
- gates
- permissions
- Form Requests
- validation
- API Resources

where appropriate.

# PHASE EXECUTION PROCESS

For the phase I specify:

## STEP 1 — READ

Read the full phase file.

## STEP 2 — AUDIT

Inspect the relevant existing implementation.

Before modifying files, identify:

- what already exists
- what is missing
- what is insecure
- what conflicts with the phase requirements

## STEP 3 — PLAN INTERNALLY

Create a concise implementation strategy.

Do not ask me to approve routine implementation decisions.

Proceed autonomously unless:

- destructive data loss is possible
- credentials/API keys are required
- an irreversible business decision is unavoidable

## STEP 4 — IMPLEMENT

Implement only the requested phase.

Do not start the next phase.

## STEP 5 — TEST

Run all checks listed in the phase file.

Also run additional relevant tests if necessary.

Fix errors introduced by your changes.

Do not stop at the first failing test without investigating.

## STEP 6 — SECURITY REVIEW

Before completion, ask:

- Can another role bypass this through a direct route?
- Can a mobile user call this manually?
- Is private data exposed through JSON?
- Is authorization only happening in the UI?
- Could status transitions be skipped?
- Could wallet/verification/enforcement rules be bypassed?

Fix significant issues.

## STEP 7 — REPORT

At the end of the phase provide:

1. Phase completed
2. Existing architecture discovered
3. Files created
4. Files modified
5. Database changes
6. Routes added/changed
7. Middleware/policies/permissions added or changed
8. Tests added
9. Commands executed
10. Test results
11. Security improvements
12. Remaining limitations
13. Anything that must be known before starting the next phase

Do not simply say "done."

# DATABASE SAFETY

Inspect migrations before adding new migrations.

Do not create duplicate columns or tables.

Do not modify old production migrations unnecessarily if a new migration is safer.

Preserve data where practical.

If a migration may destroy existing data, do not perform the destructive change automatically.

# ROUTE SAFETY

Always inspect:

`php artisan route:list`

when route changes are involved.

Administrative routes should remain clearly separated from marketplace/mobile routes.

Preferred conceptual boundary:

WEB:
`/admin/...`

MOBILE MARKETPLACE API:
`/api/v1/...`

Do not create mobile administrative endpoints.

# API SAFETY

Use API Resources or equivalent controlled serialization.

Do not expose complete Eloquent models blindly.

Particularly avoid exposing:

- password fields
- internal notes
- private identity documents
- internal verification notes
- accounting notes
- budget notes
- cashier notes
- enforcement investigation data
- internal risk information
- private audit information
- private document paths

# ROLE BOUNDARY

The permanent capability model is:

MARKETPLACE:

Customer
Provider
Sponsor / Referrer

WEB BACK OFFICE:

Super Admin
Admin
Verifier
Accounting
Budget
Cashier
Enforcement / Compliance
Maintenance

A back-office-only role should not automatically gain marketplace mobile access.

A marketplace role should not automatically gain administrative access.

# CASHOUT RESPONSIBILITY

Mobile/user:

Request Cashout
View Status

Web Accounting:

Accounting Review

Web Budget:

Budget Approval

Web Cashier:

Disbursement

Mobile/user:

Receives status / sees result

Never implement approval stages in Flutter.

# VERIFICATION RESPONSIBILITY

Mobile/user:

Submit verification documents
View status

Web Verifier:

Review
Approve
Reject
Request more information
Revoke where authorized

Never permit self-verification.

# ENFORCEMENT RESPONSIBILITY

Web enforcement staff administer:

Warning
→ Account Review
→ Temporary Restriction
→ Suspension

Mobile users may only see applicable status/messages and later appeal if implemented.

# AUDITABILITY

Important administrative actions should be auditable.

Where relevant record:

- actor
- target
- action
- before state
- after state
- timestamp
- reason/notes

# CODE QUALITY

Prefer:

- clear naming
- small focused services
- policies/middleware
- reusable validation
- tests around authorization
- minimal duplication

Avoid:

- giant controllers
- role checks scattered everywhere
- magic strings duplicated in many files
- business logic in Blade
- business logic duplicated in Flutter

# DO NOT BEGIN UNTIL I SPECIFY A PHASE

When I give you a phase filename, read and execute ONLY that phase.

Example:

`Implement PHASE_A_AUTH_AUDIT.md`

Then complete Phase A only.

Do not automatically continue to Phase B.

Wait for my next instruction after delivering the Phase A report.