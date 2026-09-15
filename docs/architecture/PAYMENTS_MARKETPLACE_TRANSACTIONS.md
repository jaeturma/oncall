# Payments, Marketplace Transactions, Fees, Refunds & Reconciliation Architecture (Phase Q)

## 1. Existing architecture discovered

Phase Q asks for production-ready marketplace payment infrastructure, and
explicitly warns against creating a second, unrelated wallet/ledger system.
A two-agent audit found mature, well-tested financial infrastructure
already in place from Phases prior to Q:

- `JobPayment` (1:1 with `Job`, unique `job_id`) already snapshots
  `gross_amount`/`platform_fee`/`net_amount` the moment a job completes,
  immutable thereafter — this **is** the "marketplace transaction" the spec
  asks for. A separate `MarketplaceTransaction` model would duplicate
  transaction rows for the same business event, which the spec explicitly
  forbids.
- `JobPaymentService` — `openFor()` (idempotent), `confirmPaid()`
  (customer self-reports payment), `release()` (Accounting/Admin moves the
  earning from pending to spendable), `reverse()`, `applyRefund()`
  (dispute-driven). States: `Pending → Paid → Released`, with `Reversed`
  reachable from either of the latter two.
- `WalletLedger`/`WalletTransaction` — append-only, balance always
  `sum()`-derived over `Posted` entries, never a mutable balance column.
  `WalletTransactionType::Refund` existed in the enum but was **unused**
  until this phase (refunds previously posted as the generic `Adjustment`).
- `CommissionEngine`/`WithdrawalWorkflow` — structurally identical
  lock-row/assert-transition/post-to-ledger/audit/notify shape, reused as
  the template for every new Phase Q service.
- `DisputeService` already owns the "admin decision → refund/reversal"
  workflow end-to-end and **delegates all money movement to
  `JobPaymentService`** — it never touches the ledger directly. Every new
  Phase Q refund path follows the same rule.
- `FinanceReportService` — read-only reconciliation/reporting; its own
  docblock states "nothing here writes."
- **Zero payment-gateway code existed anywhere** — no GCash/Maya/PayMongo/
  Stripe SDK, no webhook route. All money-in was (and remains) manual: the
  customer self-reports `payment_method`/`payment_reference` as free text.
- Money math was already bcmath-on-strings throughout (never floats).
- Only one payment-related named `Gate::define` existed
  (`view-finance-reports`); every other money-movement authorization
  (`release`, `reverse`, withdrawal review) was already Policy-based — the
  financial domain's own convention, distinct from the settings-surface
  convention (`Gate::define`) used by Phase O/P admin screens.

Phase Q's job was to extend this, not replace it.

## 2. User-confirmed decisions

Three architecture forks were put to the user via `AskUserQuestion`; the
recommended option was chosen every time:

1. **Keep instant-Paid-on-customer-confirm.** No new "Accounting verifies
   before Paid" gate was added — `release()` already gates when money
   actually becomes spendable, which satisfies the spirit of "not final
   until reviewed" without redesigning the existing, well-tested
   Pending→Paid→Released flow. A `PaymentAttempt` layer was added purely
   for safe retry-after-reversal and audit history.
2. **Manual-only payment gateway, behind a real abstraction.** No
   fabricated gateway credentials were introduced.
   `PaymentGatewayInterface`/`PaymentManager`/`ManualPaymentGateway` exist
   so the rest of the pipeline is written against an interface, but the
   only registered implementation performs no external call — it wraps the
   existing self-reported confirm/refund flow. PayMongo (GCash/Maya/cards/
   QR Ph via one integration) is documented here as the recommended future
   Philippine aggregator, not implemented.
3. **Registration fees: architecture-ready only.** A `purpose` column/enum
   exists on `JobPayment` (`SERVICE_TRANSACTION` / `RegistrationFee`,
   reserved) for forward-compatibility, but no new account-type-upgrade or
   checkout flow was built — none existed to extend, and building one was
   out of scope for this phase.

## 3. What was and was not built

**No new `MarketplaceTransaction` model.** `JobPayment` already fills that
role; extending it (new columns: `receipt_number`, `refunded_amount`,
`purpose`) was the only correct move.

**No new `App\Support\Money` helper, no mass bcmath refactor.** Every
existing financial call site already used bcmath-on-strings, which already
satisfies "no float arithmetic." New Phase Q code (`FeeCalculationService`,
`RefundEligibilityService`) uses bcmath directly, matching the house
convention, rather than adding a parallel abstraction that would only add
risk to ~40 passing tests for no safety gain.

**No generic "manage financial adjustments" UI.** The spec itself frames
this as conditional ("if manual ledger adjustments are required") and
explicitly warns against a generic "set balance" form. The existing
`reverse()` (staff) and `RefundService` (request/approve/reject) paths
already cover every realistic correction scenario without an open-ended
balance editor.

**No auto-scheduled reconciliation job.** `php artisan payments:reconcile`
exists and is documented as schedulable by ops, but nothing in
`routes/console.php` schedules it automatically this phase, to avoid
introducing a surprise recurring job as a side effect of this work.

## 4. New data model

- `payment_attempts` — one row per payment submission against a
  `JobPayment` (`method`, `status`, `gateway`, `gateway_reference`,
  `idempotency_key`, `submitted_by`). Under the instant-trust model
  (decision 1) every attempt from the manual gateway is created already
  `Verified`; `reviewed_by`/`reviewed_at`/`Rejected`/`Expired` are reserved
  for a future real-gateway or manual-review mode.
- `refunds` — one row per refund **request** (`job_payment_id`,
  `requested_by`, `amount`, `status`, `reason`, internal-only
  `decision_notes`, `wallet_transaction_id` once posted). Separate from
  `JobPayment.refunded_amount`, which is a denormalized running total
  (same pattern as `ProviderProfile.rating_cached`).
- `payment_settings` — singleton (`PaymentSetting::current()`, mirrors
  `ReviewSetting`/`LocationSetting`): accepted methods, transaction limits,
  receipt prefix, refund window/limits, and the platform fee
  type/value (reusing the existing `CommissionType` enum — no new fee-type
  enum was added).
- `reconciliation_flags` — one row per internal-consistency finding
  (`category`, optional `job_payment_id`/`payment_attempt_id`, `status`),
  unique on `(category, job_payment_id, payment_attempt_id)` so re-running
  the check is idempotent.
- `job_payments` gains `receipt_number` (nullable unique, generated once on
  the first successful `Paid` transition and never regenerated —
  `sprintf('%s-%06d', prefix, $payment->id)`, collision-free by
  construction since `id` is already unique), `refunded_amount` (decimal
  cache), and `purpose`.

New enums: `PaymentMethod`, `PaymentAttemptStatus`, `RefundStatus`,
`PaymentPurpose`, `ReconciliationCategory`, `ReconciliationStatus`.

## 5. Payment confirmation & safe retry

`JobPaymentService::confirmPaid()`'s status guard was extended from
`status === Pending` to `status in [Pending, Reversed]` (§64's "safe retry,
never overwrite historical failed attempts"): after a chargeback/dispute
reversal, the customer can submit a fresh payment attempt and reference
without discarding the earlier attempt or ledger rows — both stay exactly
as they were, a new `PaymentAttempt` and a new `JobEarning` ledger entry
are posted, and `confirmed_by`/`confirmed_at`/`payment_method`/
`payment_reference` are overwritten to reflect the successful retry, while
`receipt_number` is preserved from the first successful confirmation.

Every submission — successful or not — creates a `PaymentAttempt` row
before the ledger is touched, giving a permanent audit trail even though
the manual gateway cannot currently fail (`PaymentAttemptStatus::Rejected`/
`Expired` are exercised only once a real gateway is registered).

## 6. Fees

`FeeCalculationService::split()` replaces `JobPaymentService`'s former
inline `split()`/`platformPercent()`. The `AccountType.platform_commission_percent`
override path is preserved byte-for-byte (existing tests assert this
unchanged); only the **fallback** path — previously a raw
`config('oncall.platform.commission_percent', 15)` read — becomes
admin-configurable via `PaymentSetting.platform_fee_type`/
`platform_fee_value` (Fixed or Percentage).

## 7. Refunds

Two refund paths exist, deliberately kept separate:

- **Dispute-driven** (`DisputeService::partial()` →
  `JobPaymentService::applyRefund()`) — unchanged behavior, except the
  posted ledger entry now uses the already-existing-but-previously-unused
  `WalletTransactionType::Refund` instead of the generic `Adjustment`, and
  `JobPayment.refunded_amount` is now incremented so `refundableAmount()`
  stays accurate afterward.
- **Non-dispute** (`RefundService`, new) — a customer or back-office staff
  member can `request()` a refund; `RefundEligibilityService` (single
  source of truth, checked on both request and approval — defense in
  depth) verifies payment status, amount vs. remaining refundable amount,
  no open dispute, refund window, and per-payment request limit;
  `approve()` **still calls `JobPaymentService::applyRefund()`** rather
  than reimplementing ledger posting, exactly mirroring how `DisputeService`
  already delegates. `reject()` records a reason; only the requester is
  notified of a rejection when they are the job's own customer (a
  back-office-initiated request rejecting itself would otherwise notify
  staff about their own action).

## 8. Receipts

`ReceiptService::forPayment()` builds a sanitized, read-only payload
entirely from already-loaded relations — no new persistence. It never
exposes `notes`, `confirmed_by`, or `released_by`, matching
`JobPaymentResource`'s existing documented boundary. Access is gated by
`JobPaymentPolicy::viewReceipt()` (the payment's own customer, its
provider, or back-office) — verified by `ReceiptTest`'s IDOR checks.

## 9. Reconciliation

No external gateway exists to reconcile against, so "reconciliation" here
is internal-consistency checking — the spec's own documented fallback for
this situation. `ReconciliationService` (deliberately **not** added to
`FinanceReportService`, whose own docblock says "nothing here writes")
runs four checks and upserts idempotent `ReconciliationFlag` rows:

1. `STALE_PAYMENT` — `Paid` but unreleased for over 72 hours.
2. `STALE_ATTEMPT` — a `Pending` payment attempt past its `expires_at`
   (unreachable under the manual gateway today; ready for a real one).
3. `DUPLICATE_REFERENCE` — the same `gateway_reference` reused across
   payment attempts on different job payments — a real fraud signal even
   in manual mode.
4. `REFUND_MISMATCH` — a `JobPayment.refunded_amount` cache that no longer
   matches the sum of its posted `Refund`-typed ledger entries.

`php artisan payments:reconcile` runs all four and is documented as
schedulable by ops; it is not auto-scheduled this phase. The admin queue
(`/admin/finance/reconciliation`) lists open flags and lets Admin/Accounting
resolve one with a required note, audited via `AuditLog`.

## 10. Authorization

Two conventions, matching the codebase's own existing split:

- **Money movement** stays Policy-based: `JobPaymentPolicy` gained
  `viewReceipt()`/`requestRefund()`; the new `RefundPolicy` (`create`,
  `decide` — reusing the existing `JobPaymentPolicy::RELEASERS` constant,
  now `public`, `viewQueue`, `view`) follows the same shape as
  `JobPaymentPolicy`/`WithdrawalPolicy`.
- **Settings/administration surfaces** stay `Gate::define`-based:
  `manage-payment-settings`, `view-payment-reconciliation` — both collapse
  to `canAccessAdmin()`, matching every prior phase's settings gate.

## 11. Notifications

Reuses `job_payment_confirmed`/`job_payment_released` unchanged. Three new
event keys were added to `config/notifications.php`'s catalog:
`earning_reversed` (provider — added to both `JobPaymentService::reverse()`,
which previously notified no one, and indirectly whenever `RefundService`
reduces an unreleased earning through `applyRefund()`), `refund_completed`
(the job's customer), `refund_rejected` (the requester, only if they are
the customer).

## 12. Deliberate limitations (documented, not oversights)

- **Manual payments only.** No real payment gateway is integrated; PayMongo
  is the recommended future path (see §2).
- **No webhook surface.** There is nothing to forge because nothing
  listens for external callbacks — the manual gateway performs no
  outbound or inbound HTTP call.
- **No card data is ever collected**, stored, or transmitted — N/A by
  construction, since Oncall never processes payments directly.
- **No generic financial-adjustment UI** (see §3).
- **Reconciliation is internal-only**, not compared against a gateway
  ledger, since none exists.
- **Registration fees are architecture-ready, not collectible** (see §2).
- **`FinanceReportService::reconciliation()`'s `platform_revenue` figure does
  not back out the fee proportion of a later refund** on an already-`Released`
  payment (a pre-existing characteristic, not introduced by Phase Q, but now
  more reachable given the new self-serve refund path). Left unchanged since
  fixing it means redesigning the existing, separately-tested reconciliation
  report rather than extending it — flagged here as a known reporting
  imprecision for a future phase, not silently accepted.

## 13. Security checklist (spec §85), verified via tests

- Flutter cannot mark a payment successful — `JobPaymentController::confirm`
  is the only mutator, server-computed amounts, never trusts a client-sent
  status.
- No redirect-based success — the manual gateway has no redirect flow.
- No webhook exists to forge — N/A, documented above.
- All monetary math is bcmath-on-strings, never floats (`FeeCalculationTest`
  covers Fixed/Percentage/None/rounding/account-type-override).
- Duplicate confirmation cannot duplicate earnings — the status guard plus
  row locking (`lockForUpdate()`) prevents a double-`Paid` transition;
  `PaymentAttemptTest` covers safe retry without double-posting.
- Provider earnings are released only under `JobPaymentPolicy::release()`
  (Admin/Accounting, `Paid`, no open dispute).
- Refunds cannot exceed the remaining refundable amount
  (`RefundEligibilityService`, covered by `RefundTest`).
- Historical ledger entries are never edited — every correction is a new
  offsetting `Refund`/`Reversal` entry.
- No gateway secrets exist to expose to Flutter — N/A, no gateway.
- Marketplace roles cannot reach payment administration
  (`PaymentSettingsTest`'s marketplace-role and mobile-Sanctum-404 checks;
  `ReconciliationTest`'s marketplace-role check).
- Receipts and refunds are protected from IDOR (`ReceiptTest`,
  `RefundTest`'s stranger-forbidden check).
- No raw card CVV/PAN storage — N/A, never collected.
- A failed/rejected payment attempt cannot corrupt the job or the ledger —
  the ledger entry is only posted after the attempt is recorded as
  `Verified`.

## 14. Testing

34 new backend tests (`RefundTest`, `PaymentAttemptTest`,
`PaymentSettingsTest`, `ReconciliationTest`, `ReceiptTest`,
`FeeCalculationTest`) plus the pre-existing `JobEarningsTest`,
`DisputeTest`, `WithdrawalWorkflowTest`, `SponsorCommissionTest`,
`FinanceReportTest`, and `MobileApiJobPaymentTest` — 447 backend tests
total, all passing. 8 new Flutter model/widget tests, 54 total, all
passing. `flutter analyze` reports 0 errors (28 pre-existing informational
lints, none from Phase Q code).

One correctness bug was found and fixed during the security review pass
(not by a failing test — the default `max_refund_requests_per_payment` of
3 masked it): `RefundEligibilityService`'s re-check inside
`RefundService::approve()` counted the refund being approved against its
own request-limit slot, which would have made the last allowed refund on a
payment permanently unapprovable whenever the limit was reached exactly.
Fixed by excluding the refund's own id from that count during the
approval-time re-check; covered by
`RefundTest::test_the_only_allowed_refund_request_can_still_be_approved`.
