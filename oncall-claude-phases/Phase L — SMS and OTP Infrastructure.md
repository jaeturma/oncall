Implement a new phase for the Oncall Philippines project:

# PHASE L — SMS / OTP INFRASTRUCTURE AND ADMIN-MANAGED SMS API

Work inside the existing `oncall` project.

All Phases A–K are already implemented and must be treated as the current source of truth.

Do not redesign or weaken the authorization architecture established in previous phases.

Complete Phase L only.

Do not begin any future phase.

---

# OBJECTIVE

Implement production-ready SMS and OTP infrastructure for Oncall Philippines.

The system must support:

- mobile number verification
- OTP generation and verification
- SMS delivery
- configurable SMS providers
- administrator-managed SMS API settings
- secure storage of SMS credentials
- OTP rate limiting
- resend cooldown
- OTP expiration
- attempt limits
- delivery logging
- audit logging
- provider fail-safe behavior
- future ability to add multiple SMS providers

The Laravel backend remains the authoritative source of truth.

Flutter must never directly communicate with an SMS provider.

All OTP generation, validation, throttling, and SMS transmission must happen through Laravel.

---

# PERMANENT SECURITY BOUNDARY

Preserve the architecture established in Phases A–K.

Mobile marketplace users remain:

- Customer
- Service Provider
- Sponsor / Referrer

SMS provider configuration is WEB ADMIN ONLY.

The following must never be available from Flutter/mobile APIs:

- SMS API credentials
- API secret
- API token
- sender configuration management
- provider configuration
- provider testing configuration endpoints
- SMS logs containing sensitive provider information
- OTP administration
- manual OTP viewing
- secret keys
- internal delivery debug payloads

Only authorized web administrators or maintenance/configuration personnel may manage SMS settings.

Enforce this server-side.

Do not rely only on hiding admin menu items.

---

# STEP 1 — AUDIT EXISTING IMPLEMENTATION

Before making changes, inspect the existing project for:

- User mobile number fields
- phone/mobile verification fields
- existing OTP code
- existing notification classes
- Laravel Notifications
- queued jobs
- SMS libraries
- HTTP clients
- Sanctum authentication
- registration flow
- login flow
- verification workflows from Phase H
- admin settings architecture
- role and permission architecture
- audit logging architecture
- encryption patterns
- rate limiting
- cache/Redis usage
- existing settings/config tables

Do not create duplicate fields, tables, services, middleware, or settings systems if suitable implementations already exist.

Reuse the existing architecture where possible.

Document important findings before modifying anything.

---

# STEP 2 — SMS PROVIDER ABSTRACTION

Create a provider-independent SMS architecture.

Do NOT hard-code the application directly to one SMS company.

Create concepts equivalent to:

```php
SmsManager
SmsProviderInterface
SmsMessage
SmsResult
```

Example interface:

```php
interface SmsProviderInterface
{
    public function send(string $mobile, string $message): SmsResult;

    public function testConnection(): SmsResult;
}
```

The exact design may differ if the project already has a better service/action architecture.

The goal is that application code can call something conceptually equivalent to:

```php
$smsManager->send($mobile, $message);
```

without knowing which SMS provider is currently active.

Provider-specific code must be isolated.

---

# STEP 3 — ADMIN-MANAGED SMS API CONFIGURATION

Create a secure web admin page for SMS configuration.

Suggested route area:

```text
/admin/settings/sms
```

or integrate into the existing System Configuration section if that architecture already exists.

Administrators should be able to configure:

- SMS provider
- provider display name
- API base URL if required
- API key
- API secret/token
- sender ID / sender name
- username if required
- password if required
- route/channel if required
- default country code
- enabled / disabled
- test mobile number
- OTP message template
- general SMS template settings where appropriate

Do not assume every provider uses all fields.

Use provider-specific configuration fields or a flexible secure configuration mechanism.

The admin interface should support future providers without requiring major architectural changes.

---

# SMS CREDENTIAL SECURITY

SMS credentials are secrets.

They must NOT be stored as ordinary plaintext configuration visible to users.

Use Laravel encryption or the existing encrypted-settings architecture.

Sensitive fields should be encrypted at rest.

Examples:

- API key
- API secret
- bearer token
- password
- private authentication credentials

When editing existing credentials:

- never send the actual secret back to the browser unless absolutely necessary
- display masked values such as:

```text
••••••••••••A92F
```

- leaving a credential field blank should preserve the existing value
- replacing a credential should overwrite it securely

Never include credentials in:

- logs
- exception messages
- API responses
- browser HTML source
- Flutter API payloads
- audit log metadata
- debugging output

---

# STEP 4 — INITIAL SMS PROVIDERS

Build the architecture so additional providers are easy to add.

If the project does not already specify an SMS provider, implement at minimum:

```text
Generic HTTP SMS Provider
```

This provider should allow an administrator to configure an HTTP-based API safely.

Where practical, support common authentication patterns such as:

- Bearer token
- API key header
- basic authentication
- request parameter API key

However:

DO NOT build an unsafe arbitrary HTTP request builder that allows unrestricted SSRF or access to local/internal infrastructure.

Validate configured API URLs.

Reject dangerous destinations such as:

```text
localhost
127.0.0.1
::1
169.254.x.x
private/internal network ranges
```

unless the application already has a deliberate trusted internal-provider architecture.

The generic provider should have a defined configuration schema, not arbitrary executable code.

---

# STEP 5 — ACTIVE SMS PROVIDER

The admin must be able to:

- enable SMS
- disable SMS
- choose the active provider
- configure the provider
- save configuration
- test SMS delivery

Only one provider needs to be active for MVP unless existing architecture naturally supports priority/fallback providers.

Design the backend so fallback providers can be added later.

---

# STEP 6 — TEST SMS FUNCTION

Provide a WEB ADMIN ONLY function:

```text
Send Test SMS
```

The admin enters or selects a test mobile number.

The system sends something similar to:

```text
Oncall Philippines SMS test successful.
```

Display a controlled result:

```text
Test SMS sent successfully.
```

or

```text
SMS provider rejected the request.
```

Do not expose full provider API responses to the browser.

Detailed technical information may be logged securely, but must have secrets redacted.

Test SMS actions must be audit logged.

---

# STEP 7 — MOBILE NUMBER NORMALIZATION

Create one canonical mobile number normalization service.

Philippine numbers should be normalized consistently.

For example:

```text
09171234567
9171234567
+639171234567
639171234567
```

should resolve to a canonical format such as:

```text
+639171234567
```

Do not scatter normalization logic throughout controllers.

Use a reusable service/value object/helper.

Validate Philippine mobile numbers appropriately.

Keep the design extensible for international numbers later.

---

# STEP 8 — OTP SERVICE

Create a dedicated OTP service.

Conceptually:

```php
OtpService
```

Responsibilities:

- generate OTP
- securely store OTP state
- send OTP
- verify OTP
- expire OTP
- enforce resend cooldown
- limit attempts
- invalidate OTP after successful verification
- invalidate old OTP when a replacement OTP is generated
- record verification result
- prevent replay

Recommended OTP:

```text
6 digits
```

Use cryptographically secure random generation.

Do NOT use predictable random values.

Example:

```php
random_int(100000, 999999);
```

---

# DO NOT STORE OTP IN PLAINTEXT

Do not persist the OTP code as ordinary plaintext.

Store a hash of the OTP.

Conceptually:

```php
Hash::make($otp)
```

or use another appropriate secure hashing approach.

The original OTP should exist only long enough to construct the outgoing message.

Do not write OTP values to:

- logs
- database plaintext
- audit logs
- exceptions
- debug output

---

# STEP 9 — OTP EXPIRATION

Make OTP expiration configurable by admin or application settings.

Recommended default:

```text
5 minutes
```

Provide sensible bounds.

For example:

```text
Minimum: 2 minutes
Maximum: 15 minutes
```

Do not allow an OTP to remain valid indefinitely.

---

# STEP 10 — RESEND COOLDOWN

Implement resend protection.

Recommended default:

```text
60 seconds
```

The user must not be able to repeatedly request OTP messages.

Return a controlled API response such as:

```json
{
  "message": "Please wait before requesting another verification code.",
  "retry_after": 42
}
```

Do not generate another OTP during the cooldown.

---

# STEP 11 — OTP REQUEST RATE LIMITING

Protect OTP endpoints against abuse.

Apply limits based on appropriate combinations of:

- user ID
- mobile number
- IP address
- device/session where available

Suggested protections:

```text
Maximum OTP requests per mobile number per hour
Maximum OTP requests per IP per hour
Maximum OTP verification attempts per OTP
```

Use Laravel RateLimiter, cache, Redis, database controls, or the existing project infrastructure.

Do not rely solely on frontend timers.

The server must enforce all limits.

---

# STEP 12 — OTP ATTEMPT LIMIT

Limit incorrect verification attempts.

Recommended default:

```text
5 attempts
```

After the maximum attempts:

- invalidate the OTP
- require another OTP request after applicable cooldown/rate limits

Do not tell an attacker whether individual digits were correct.

Use generic errors:

```text
Invalid or expired verification code.
```

---

# STEP 13 — OTP PURPOSE

An OTP must be bound to a purpose.

Examples:

```text
mobile_verification
registration
login
password_reset
sensitive_action
mobile_change
```

For this phase, implement the purposes actually needed by current project flows.

At minimum support:

```text
mobile_verification
```

Do not allow an OTP created for one purpose to authorize another purpose.

OTP records/state should conceptually include:

```text
user_id
mobile
purpose
otp_hash
expires_at
attempts
max_attempts
sent_at
verified_at
consumed_at
request_ip
```

Adapt this to existing architecture.

---

# STEP 14 — MOBILE VERIFICATION FLOW

Integrate SMS OTP into user mobile verification.

Typical flow:

```text
User registers or adds mobile number
        ↓
Laravel validates and normalizes number
        ↓
Laravel generates OTP
        ↓
Laravel sends OTP through active SMS provider
        ↓
User enters OTP
        ↓
Laravel verifies OTP
        ↓
mobile_verified_at is populated
```

Reuse existing verification state from previous phases where appropriate.

Do not create competing verification fields if equivalent fields already exist.

---

# STEP 15 — MOBILE API ENDPOINTS

Create or reuse secure marketplace API endpoints conceptually similar to:

```text
POST /api/v1/mobile-verification/request
POST /api/v1/mobile-verification/verify
POST /api/v1/mobile-verification/resend
```

Exact URLs may follow existing project conventions.

These endpoints must:

- use server-side validation
- use canonical mobile normalization
- apply rate limiting
- never expose OTP
- never expose SMS credentials
- return controlled validation errors
- respect account restrictions established in previous phases

If authentication is not yet possible during registration, design the pre-auth verification endpoint carefully.

Prevent number enumeration where possible.

---

# STEP 16 — REGISTRATION INTEGRATION

Inspect the current registration process.

Integrate mobile verification without unnecessarily breaking the existing user registration UX.

Choose the approach most compatible with the existing codebase.

Possible flow:

```text
Create registration session
↓
verify mobile via OTP
↓
complete account registration
```

OR:

```text
create account as unverified
↓
send OTP
↓
restrict sensitive marketplace actions until mobile verification succeeds
```

Use the existing project architecture and previous-phase security rules to select the safer approach.

Document the chosen flow.

---

# STEP 17 — MOBILE NUMBER CHANGE

If profile mobile-number editing already exists, secure it.

Changing a verified mobile number must:

1. validate the new number
2. normalize the new number
3. mark the new number unverified
4. send OTP to the new number
5. require successful verification before treating it as verified

Do not allow the user to keep `mobile_verified_at` from the old number when changing to another mobile number.

Where useful, preserve the old verified number until the new number has been confirmed.

Choose the safest pattern compatible with existing architecture.

---

# STEP 18 — FLUTTER INTEGRATION

Flutter remains marketplace-only.

Implement or prepare the mobile-side integration needed for:

- request OTP
- enter OTP
- resend OTP
- resend countdown
- verification success
- expired OTP
- incorrect OTP
- too many attempts
- SMS temporarily unavailable

Flutter must NEVER:

- generate OTP
- validate OTP locally
- contain SMS API keys
- contact the SMS provider directly
- store SMS provider credentials
- infer verification based only on UI state

Laravel decides whether verification succeeded.

Flutter should render the backend state.

---

# STEP 19 — SMS DELIVERY LOG

Create an SMS delivery log if an equivalent system does not already exist.

Suggested fields:

```text
id
user_id nullable
mobile_masked or securely stored normalized mobile
provider
purpose
message_type
provider_message_id nullable
status
queued_at
sent_at nullable
failed_at nullable
error_code nullable
created_at
updated_at
```

Do not store OTP text.

Prefer not to store the full SMS body for OTP messages.

Instead store:

```text
message_type = otp_verification
```

If message bodies are retained for non-OTP SMS, ensure sensitive content is not logged.

Statuses may include:

```text
queued
sent
accepted
delivered
failed
rejected
```

Adapt to provider capabilities.

---

# STEP 20 — ADMIN SMS LOG VIEWER

Provide an authorized WEB ADMIN view of SMS delivery history.

Suggested:

```text
/admin/sms/logs
```

or under system settings.

Allow filters such as:

- date
- provider
- status
- SMS type
- user
- masked mobile number

Never display:

- OTP code
- SMS API key
- provider secret
- full provider authentication response

Mask mobile numbers where appropriate.

Example:

```text
+63917****567
```

Follow existing admin permission architecture.

---

# STEP 21 — AUDIT LOGGING

Audit important SMS configuration actions:

- SMS enabled
- SMS disabled
- provider changed
- sender ID changed
- API credentials replaced
- configuration updated
- test SMS sent
- OTP policy changed

Do NOT place the actual secret in audit logs.

Instead record safe metadata such as:

```text
API credentials updated
```

not:

```text
API key changed from abc to xyz
```

Audit:

- administrator
- action
- timestamp
- affected setting/provider
- request IP if existing audit architecture includes it

---

# STEP 22 — ADMIN OTP POLICY SETTINGS

Allow authorized administrators to configure safe OTP policy parameters.

Suggested settings:

```text
OTP length
OTP expiry minutes
resend cooldown seconds
maximum verification attempts
maximum sends per hour per mobile
maximum sends per hour per IP
```

Use safe min/max bounds.

Recommended default values:

```text
OTP length: 6
Expiry: 5 minutes
Cooldown: 60 seconds
Maximum attempts: 5
Maximum sends/mobile/hour: 5
Maximum sends/IP/hour: 20
```

Do not allow unsafe settings such as:

```text
OTP expiry = 24 hours
Cooldown = 0
Unlimited attempts
```

unless there is a deliberate development environment override.

Production defaults must remain secure.

---

# STEP 23 — SMS MESSAGE TEMPLATE

Allow admin to manage the OTP message template.

Default:

```text
Your Oncall Philippines verification code is {{otp}}. It expires in {{minutes}} minutes. Do not share this code with anyone.
```

Only allow supported placeholders.

Example:

```text
{{otp}}
{{minutes}}
{{app_name}}
```

Do not allow arbitrary Blade/PHP execution.

Treat templates as plain text with controlled token replacement.

Validate the final message length.

Keep SMS cost in mind.

---

# STEP 24 — QUEUED SMS DELIVERY

If Laravel queues are already configured, send SMS through queued jobs.

Conceptually:

```text
SendSmsJob
```

However, OTP UX must remain practical.

If queued delivery is used:

- enqueue immediately
- track send status
- handle failures
- avoid generating multiple OTPs due to job retry
- ensure retries send the same intended verification message safely

Do not regenerate OTP inside job retries.

If queues are not properly configured in the project, use the safest current architecture and document what would be required for production queues.

---

# STEP 25 — PROVIDER FAILURE HANDLING

Handle provider outages gracefully.

If sending fails:

- do not expose raw provider errors
- return a controlled message
- record a safe delivery failure
- log sanitized technical details
- allow retry according to business rules

Example user response:

```text
We could not send the verification code at this time. Please try again shortly.
```

If the provider rejected the request before accepting the SMS, decide carefully whether the OTP request should consume the resend window.

Use a sensible consistent policy and document it.

---

# STEP 26 — HTTP SECURITY

All SMS API communication must use HTTPS in production.

Apply:

- connection timeout
- request timeout
- sanitized exception handling
- no secret logging
- SSL certificate verification

Do not disable TLS certificate verification.

Do not use:

```php
Http::withoutVerifying()
```

in production implementation.

---

# STEP 27 — DATABASE SAFETY

Before creating migrations:

- inspect existing migrations
- inspect verification-related fields
- inspect settings tables
- inspect notification tables
- inspect audit tables

Do not create duplicate:

- mobile columns
- verification timestamps
- settings tables
- audit systems
- OTP tables

If schema changes are required, create safe forward migrations.

Do not destructively rewrite old production migrations unless absolutely appropriate.

---

# STEP 28 — ADMIN PERMISSIONS

Use the authorization framework established in previous phases.

Add permissions/capabilities equivalent to:

```text
manage_sms_settings
view_sms_logs
send_test_sms
manage_otp_policy
```

Exact naming should follow existing project conventions.

Super Admin may inherit them.

Maintenance/System Configuration users may receive only permissions explicitly appropriate to their role.

Accounting, Budget, Cashier, Verification, Enforcement, Customer, Provider, and Sponsor must NOT automatically receive SMS configuration permissions.

Test these boundaries.

---

# STEP 29 — TESTING

Create comprehensive tests.

At minimum test:

### SMS configuration

- authorized admin can view SMS settings
- authorized admin can update SMS settings
- unauthorized roles receive 403
- SMS secrets are encrypted
- existing secret remains unchanged when blank update field submitted
- secrets are never returned in HTML/API payloads

### Provider

- configured provider sends expected request
- provider timeout handled safely
- provider failure handled safely
- credentials not logged
- unsafe provider URL rejected if generic provider supports configurable URL

### OTP

- OTP request succeeds
- OTP stored as hash, not plaintext
- valid OTP verifies
- invalid OTP rejected
- expired OTP rejected
- used OTP cannot be reused
- new OTP invalidates previous OTP
- wrong-purpose OTP rejected
- attempt limit enforced
- resend cooldown enforced
- per-mobile request limit enforced
- per-IP request limit enforced

### Mobile verification

- verified mobile gets correct verification state
- mobile number change invalidates old verification appropriately
- Flutter/mobile API never receives OTP value
- mobile API never exposes SMS configuration

### Authorization

- Customer cannot manage SMS
- Provider cannot manage SMS
- Sponsor cannot manage SMS
- Verification staff cannot manage SMS unless explicitly granted
- Accounting cannot manage SMS
- Budget cannot manage SMS
- Cashier cannot manage SMS
- Enforcement cannot manage SMS
- authorized Admin can manage SMS
- mobile token cannot access web SMS configuration endpoints

### Audit

- SMS settings update produces safe audit entry
- API credential update does not log secret
- test SMS is audited

Run all relevant existing Phase A–K tests as regression checks.

Fix failures introduced by Phase L.

---

# STEP 30 — SECURITY REVIEW

Before declaring Phase L complete, act as an attacker/security reviewer.

Attempt to identify realistic bypasses such as:

- requesting unlimited OTPs
- bypassing cooldown by changing endpoint
- using old OTP after resend
- OTP replay
- using OTP for wrong purpose
- changing mobile after verification without reverification
- brute forcing six-digit OTP
- calling admin SMS routes from a marketplace account
- calling admin routes using Sanctum mobile token
- retrieving encrypted SMS credentials
- exposing secrets through validation errors
- exposing provider response bodies
- accessing SMS logs without authorization
- injecting template code
- manipulating configured SMS endpoint for SSRF
- bypassing mobile number normalization
- using race conditions to verify an invalidated OTP

Fix realistic vulnerabilities before completion.

---

# STEP 31 — DOCUMENTATION

Create:

```text
docs/architecture/SMS_OTP_ARCHITECTURE.md
```

Document:

- SMS architecture
- active provider resolution
- provider interface
- admin configuration
- secret storage
- mobile normalization
- OTP lifecycle
- expiration
- cooldown
- attempts
- rate limits
- mobile verification flow
- mobile/API flow
- admin permissions
- SMS logs
- audit behavior
- queue behavior
- failure handling
- security protections
- how to add another SMS provider

Also document required `.env` values only if some environment-level values remain necessary.

Do not put real secrets in documentation.

---

# STEP 32 — FINAL QUALITY CHECK

Run appropriate commands such as:

```bash
php artisan optimize:clear
php artisan route:list
php artisan test
```

If Flutter was modified:

```bash
flutter analyze
flutter test
```

Also inspect:

- migrations
- routes
- middleware
- policies
- permissions
- SMS services
- API Resources
- admin controllers
- Blade pages
- logs
- secret masking
- tests

Do not stop simply because the code compiles.

Fix issues found during testing and review.

---

# FINAL IMPLEMENTATION REPORT

At completion provide:

1. Phase L completion status
2. Existing architecture discovered
3. SMS provider architecture implemented
4. SMS admin settings implemented
5. Permissions added
6. OTP architecture implemented
7. OTP security controls
8. Mobile verification integration
9. API routes added/changed
10. Web admin routes added/changed
11. Database migrations created
12. Models/services/actions/jobs created
13. Flutter changes, if any
14. SMS logging implementation
15. Audit logging implementation
16. Security protections added
17. Files created
18. Files modified
19. Tests added
20. Commands executed
21. Test results
22. Remaining limitations
23. Recommended next phase

Before declaring completion, perform a final security review specifically checking that no SMS API credential, OTP value, or back-office SMS configuration can ever be obtained through the marketplace/mobile API.

Complete Phase L only.
Do not begin Phase M.