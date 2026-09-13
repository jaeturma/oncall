# SMS / OTP Architecture — Phase L

Date: 2026-09-13
Scope: production-ready SMS delivery and OTP verification, admin-managed provider configuration, and the mobile-verification flow it replaces from Phase A/H. Builds on the boundary already established in `WEB_MOBILE_SECURITY_BOUNDARY.md` (Phase K) — every rule there still holds; this only adds to it.

## 1. What existed before this phase

Audited before writing any code (Step 1 of the phase spec):

- `MobileVerificationService` (now removed) generated a 6-digit code, hashed it, and stored it in `Cache` under `mobile-verification-otp:{user_id}` with a 10-minute TTL — no cooldown, no attempt limit, no rate limit, no persistent record, and it logged the **plaintext** code (`Log::info(..., ['code' => $code])`).
- No SMS gateway existed at all — the web flow surfaced the code in the flash message as a "demo code" in non-production.
- `User.phone` / `User.phone_verified_at` already existed and are reused unchanged.
- No settings/config table of any kind existed anywhere in the app.
- `AuditLog` (actor, event, subject, before/after JSON, IP, user agent) already existed and is reused for every SMS/OTP admin action.
- No queue jobs existed anywhere (`QUEUE_CONNECTION=database`, `app/Jobs/` didn't exist).

## 2. SMS provider architecture

```
SmsManager::send(SmsMessage, ?User) : SmsResult
        │
        ├─ reads SmsSetting::current() (enabled? which provider?)
        ├─ writes a queued SmsDeliveryLog row
        ├─ resolves the provider's driver → SmsProviderInterface
        │       └─ GenericHttpSmsProvider (only driver today)
        └─ updates the log row with the final status
```

- `SmsProviderInterface` (`send`, `testConnection`) is the only thing application code depends on. `SmsManager` never references a concrete provider class directly — it matches `SmsProvider::$driver` (an `SmsProviderDriver` enum) to an implementation.
- **Adding a new provider**: add an `SmsProviderDriver` case, a class implementing `SmsProviderInterface`, and one arm in `SmsManager::resolveDriver()`. No schema change — `sms_providers.config` is a single JSON blob, shaped however that driver needs.
- `GenericHttpSmsProvider` sends a fixed-shape JSON POST (`to`, `message`, `sender_id`) with one of four auth patterns (`SmsAuthType`: bearer token, API-key header, basic auth, query param). It is deliberately **not** an arbitrary HTTP request builder — the request shape and auth patterns are the only things configurable, not the method, headers, or body structure. This is the mitigation for "admin-configured provider becomes an SSRF tool."

## 3. Admin configuration (`/admin/settings/sms`, `/admin/sms/logs`)

- One combined settings page: SMS on/off, provider selection + its config, and every OTP policy bound, backed by `SmsSetting` (a true singleton — `SmsSetting::current()` always resolves id 1) and the currently-active `SmsProvider` row.
- Gated by four named gates (`manage-sms-settings`, `view-sms-logs`, `send-test-sms`, `manage-otp-policy`), all currently Admin-only — ADR-001 has no separate Maintenance/System-Configuration role, so these collapse the same way every other back-office concept does. Named separately (not inlined `canAccessAdmin()` calls) so a future split doesn't mean auditing every call site.
- **Web-only by construction**: there is no `/api/v1` route anywhere for any of this. Not gated-and-rejected — structurally absent.
- **SSRF**: `SmsUrlValidator` rejects the provider base URL if it isn't `http(s)`, resolves (or is written) to a loopback/link-local/private/reserved address, or targets `localhost`/`.local`. Checked twice: once in `SafeSmsUrl` at save time, and again in `GenericHttpSmsProvider` immediately before every request (defense in depth against a config that was valid when saved but shouldn't be trusted blindly later). DNS resolution failure is **not** treated as unsafe — only a *successful* resolution to a disallowed address is — since the app has no way to distinguish "attacker's rebinding domain" from "this sandbox has no outbound DNS," and a failed resolution can't reach anywhere at request time either way.
- **Message template**: plain text with three recognized placeholders (`{{otp}}`, `{{minutes}}`, `{{app_name}}`) enforced by `SafeSmsTemplate` — any other `{{`, a Blade `{!!`, an `@directive(`, or a `<?php` tag is rejected. There is no template engine involved; replacement is a literal `str_replace`.
- **OTP policy bounds are hard limits, not UI hints**: length 4–8, expiry 2–15 minutes, cooldown 30–600 seconds, max attempts 3–10, hourly caps 1–50 (per mobile) / 1–200 (per IP). An admin cannot configure a 0-second cooldown or "unlimited" attempts through this form.

## 4. Credential security

- `sms_providers.config` is a single `encrypted:array`-cast column — the whole JSON blob (base URL, auth type, sender id, and the one secret field, `credential`) is encrypted at rest by Eloquent's cast, not by a bespoke encryption call site.
- `SmsProvider::maskedConfig()` is the only thing ever handed to a view or JSON response — it replaces `credential` with `••••••••<last 4 chars>`. The raw `config` accessor is used exactly twice in the codebase: inside `SmsManager`/`GenericHttpSmsProvider` (to actually authenticate) and inside `SmsSettingController::update()` (to read the *existing* credential when the form field was left blank).
- Leaving the credential field blank on the settings form preserves the existing value; submitting a new one overwrites it. Enforced in `SmsSettingController::update()`, not the browser.
- The credential is never written to: the audit log (`sms_settings.updated`/`sms_settings.test_sent` record only booleans/names, never the value), `SmsDeliveryLog` (no body/message column exists at all), or an HTTP failure log (`GenericHttpSmsProvider::sanitize()` redacts the credential out of any logged provider error body before it's written).
- A failed test SMS or real send never returns the provider's raw response to the browser — only a fixed "Test SMS sent successfully." / "SMS provider rejected the request." pair.

## 5. Mobile number normalization

`App\Services\MobileNumberNormalizer` is the one place a Philippine mobile number is canonicalized, to `+639XXXXXXXXX`. `09171234567`, `9171234567`, `+639171234567`, and `639171234567` all normalize identically. Used at every point a phone number enters the system: registration (web + mobile — Phase L closed a real gap here, see §8), the mobile-verification request flow, and the resend/verify flow (which re-normalizes defensively even though the stored value should already be canonical).

## 6. OTP lifecycle (`OtpService`)

```
request(user, mobile, purpose, ip)
  1. normalize the mobile — reject if not a valid PH number
  2. assertNotInCooldown   — 429 with Retry-After if too soon since the last send
  3. assertUnderHourlyCaps — 429 if per-mobile or per-IP cap reached this hour
  4. generate a code (random_int, not predictable random)
  5. supersede any still-open code for this user+mobile+purpose (consumed_at = now)
  6. persist the new OtpCode row — otp_hash only, never the plaintext
  7. render the message template and send via SmsManager
  8. return {sent, mobile, expires_in, resend_in, plainCode (non-prod only), smsErrorMessage}

verify(user, mobile, purpose, code)
  1. find the latest unconsumed OtpCode for this exact user+mobile+purpose
  2. reject (generic "Invalid or expired verification code.") if: none found,
     expired, already consumed, or attempts exhausted
  3. Hash::check the code; wrong guess increments attempts (locking the row
     for update first) and exhausts the code once max_attempts is hit
  4. right code → verified_at + consumed_at = now(), returns true
```

- **Purpose binding**: every query is scoped by `purpose` (an `OtpPurpose` enum — only `MOBILE_VERIFICATION` exists today). A code issued for one purpose cannot verify another, even if the same user/mobile pair somehow had two outstanding codes.
- **Replay/reuse**: `consumed_at` is set the instant a code succeeds *or* exhausts its attempts — a used or dead code can never verify again, even with the correct value in hand.
- **Race conditions**: `verify()` runs inside a DB transaction with `lockForUpdate()` on the OTP row, so two concurrent verify attempts against the same code can't both succeed or both silently double-increment past the attempt limit. `request()`'s cooldown/rate-limit *check* is not similarly locked against its own *write* — see §8 for the resulting known limitation.
- **Generic failure message everywhere**: whether a code is wrong, expired, already used, or belongs to the wrong purpose, `verify()` returns `false` and the controller always says the same "Invalid or expired verification code." — never enough for an attacker to distinguish "warm" from "dead" guesses.

## 7. Mobile verification flow

- **Web** (`/verification/mobile`, `/verification/mobile/confirm`, unchanged routes): `MobileVerificationController` now delegates entirely to `OtpService` instead of the removed `MobileVerificationService`.
- **Mobile API** (new, Phase L): `POST /api/v1/mobile-verification/{request,resend,verify}`, all behind the same `auth:sanctum` + `account.active` + `can:use-mobile` group as every other mobile route. `request` takes a `mobile` param (for a first attempt or changing numbers); `resend` reuses the user's currently-pending number; both are the same `OtpService::request()` under the hood, so cooldown/rate-limiting is identical regardless of which endpoint is called — there's no way to reset the cooldown by switching between them.
- **Changing an already-verified number**: both the web and mobile "request" actions unconditionally clear `phone_verified_at` to null *before* issuing a new code, so a user can never keep their old verified badge against a new, unverified number.
- **Demo mode**: with no SMS provider configured (this app's actual current state — there is still no paid SMS gateway), `SmsManager::send()` correctly reports `sent: false`, but `OtpService` still generates and persists a real, hashable code and returns it as `plainCode` **only when `app()->isProduction()` is false**. Both controllers treat a demo code as good enough to let the user proceed (matching the pre-Phase-L demo experience); a genuine send failure with no demo code available is a hard `503`. In production with SMS disabled, there is no demo fallback — the request simply fails with a controlled message.
- **Rate-limit response shape**: `TooManyRequestsHttpException` carries the `Retry-After` header (standard Laravel/Symfony behavior); the mobile controller additionally shapes the JSON body to `{"message": ..., "retry_after": <seconds>}` per the phase spec, since Laravel's default exception rendering only sets the header, not a body field.

## 8. Known limitations

- **Cooldown/rate-limit check-then-write race**: `OtpService::request()` checks cooldown and hourly caps, then writes a new `OtpCode` row, without a mutex spanning both steps. Two near-simultaneous requests could both pass the check before either writes, sending one extra message within the cooldown window. This is a minor rate-limit evasion, not a verification-security hole (the codes it creates are still individually hashed, expiring, attempt-limited, and purpose-bound) — mirrors the same class of accepted risk as other services in this app (e.g. `WithdrawalWorkflow` locks the specific row being mutated, not a broader "check many rows then insert" mutex).
- **No queued SMS delivery**: Step 24 of the phase spec asks for queued delivery if queues are configured (they are — `QUEUE_CONNECTION=database`). This was deliberately **not** done: a database queue driver persists a job's payload as a database row until it's processed, and the OTP message body (containing the plaintext code) would sit there in plaintext — directly contradicting "do not persist the OTP in plaintext." OTP SMS is sent synchronously instead. If queued delivery is wanted later, it needs either a non-persistent queue backend (Redis) or an encrypted job payload — plain `ShouldQueue` on a database connection is not safe for this specific payload.
- **No delivery-status webhooks**: `SmsDeliveryStatus` includes `Accepted`/`Delivered` for schema completeness, but nothing ever produces them — `GenericHttpSmsProvider` only knows "the HTTP call succeeded or failed," not whether the carrier actually delivered the message. Wiring a provider's delivery-receipt webhook is future work.
- **Existing phone numbers predate normalization**: any `users.phone` value written before this phase (if any exist) may not be in canonical `+639XXXXXXXXX` form. No backfill migration was run. Registration and the mobile-verification flow both normalize going forward, so this only affects historical data, if any.

## 9. Commands run for this phase

```
php artisan optimize:clear
php artisan route:list --path=admin/settings
php artisan route:list --path=admin/sms
php artisan route:list --path=mobile-verification
php artisan test          → 272 passed, 904 assertions
flutter analyze           → 0 errors (14 pre-existing style infos)
flutter test              → 13 passed
```
