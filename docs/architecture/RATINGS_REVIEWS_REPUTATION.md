# Ratings, Reviews & Provider Reputation Architecture (Phase P)

## 1. Existing architecture discovered

Phase P extended, rather than replaced, substantial infrastructure already
in place from earlier phases:

- `Review` model/table (`job_id`, `reviewer_id`, `reviewee_id`, `rating`,
  `comment`), unique on `(job_id, reviewer_id)` — one review per participant
  per job, already supporting **both directions** (a customer reviewing a
  provider, or a provider reviewing a customer).
- `ReviewService::create()` — eligibility (job `Completed`, reviewer is a
  participant, not already reviewed) and a full `AVG`/`COUNT` recompute of
  `rating_cached` on both `User` and `ProviderProfile`, previously
  duplicated inline.
- `ReviewPolicy` — `create` implemented; `update`/`delete` already
  hard-coded `false` (reviews were already immutable, with no withdrawal
  path).
- `POST /jobs/{job}/reviews` on both web and `/api/v1`.
- `JobStatus` (`Accepted/OnTheWay/InProgress/Completed/Cancelled/Disputed`)
  — completion is unilateral: only the provider transitions a job to
  `Completed` (`Job::allowedTransitionsFor()`).
- Disputes: a `Completed` job can be disputed (→ `Disputed`, restorable to
  `Completed` on rejection) — already tested.
- `UserReport` (Phase I) is hard-coded to `Job`+`User` — not a generic
  polymorphic reporting mechanism.
- Every phase-specific admin gate (SMS, notifications, location) collapses
  to `$user->canAccessAdmin()` per ADR-001 (no Enforcement/Compliance
  sub-role exists).

Phase P built the missing layer — moderation, reporting, provider
responses, reputation aggregation, notifications, admin settings — directly
onto this foundation.

## 2. Completion trust model

> A marketplace rating must come from a real Oncall Philippines service
> transaction.

Enforced structurally: `reviews.job_id` is a required foreign key to
`jobs`, and every eligibility/authorization check (`ReviewEligibilityService`,
`ReviewPolicy`) resolves the reviewer/reviewee from that job's own
`service_finder_id`/`provider_id` columns — never from client input. A
review can only ever exist for a job that reached `JobStatus::Completed`.

**Completion workflow decision (user-confirmed)**: completion stays
**single-step and unilateral** — no new "provider marks complete → customer
confirms" state was introduced, and no auto-completion timeout exists. The
existing, separately-tested payment-confirmation step (`JobPaymentController::confirm`,
finder-only) already gives the customer a real checkpoint on the
transaction. Phase P spec §6/§7 (customer non-response, auto-completion
policy) is therefore **not applicable** — documented here rather than
implemented, per the spec's explicit permission to preserve existing
authoritative completion behavior rather than redesign the booking engine.

## 3. Review eligibility

Centralized in `App\Services\ReviewEligibilityService::canReview(Job $job, User $user)`,
the single source of truth both `ReviewPolicy::create()` (a boolean gate)
and `ReviewService::create()` (a defense-in-depth re-check inside the
locked transaction) call — no duplicated logic. Checks, in order:

1. `$user` is one of the job's two actual participants.
2. Not already reviewed by this user for this job.
3. `$job->status === JobStatus::Completed` exactly — never `Disputed`, even
   if the job was `Completed` immediately before a dispute was opened
   (`DisputeService::open()` flips status to `Disputed`, which fails this
   check until/unless the dispute is resolved back to `Completed`).
4. `ReviewSetting::current()->reviews_enabled`.
5. Within the admin-configured `review_window_days` of `job->completed_at`
   (default 30 days).

Exposed to Flutter via `GET /api/v1/jobs/{job}/review-eligibility` (adapted
from the spec's illustrative `service-requests/{sr}/review-eligibility`
path, since reviews anchor to `Job` in this schema, not `ServiceRequest`)
— gated by `JobPolicy::view`, so a non-participant gets a flat 403 rather
than a "false" answer about a job they have no relationship to.

## 4. One review per completed job

The existing unique constraint `(job_id, reviewer_id)` remains the
authoritative guard — both at the database layer (race-condition-safe) and
re-checked inside `ReviewService::create()`'s row-locked transaction. A
second submission attempt is rejected at the `StoreReviewRequest::authorize()`
step (via the policy) before the service layer is even reached, matching
the pre-existing test expectation (`403`, not `409` — the service's
reviewed-aware 409 path is defense-in-depth for any future caller that
bypasses the FormRequest, not reachable through the current routes).

## 5. Review model / schema

`reviews` gained: `status` (`PUBLISHED`/`HIDDEN`/`REMOVED`/`WITHDRAWN`,
default `PUBLISHED`), `response` (text, nullable), `responded_at`,
`moderated_at`. New `review_reports` table (`review_id`, `reporter_id`,
`category`, `description`, `status` — reuses the existing `ReportStatus`
enum, `reviewed_by`, `reviewed_at`, `moderation_notes`), unique on
`(review_id, reporter_id)`. New singleton `review_settings` table (mirrors
`location_settings`/`sms_settings`).

## 6. Rating scale

Unchanged: `rating` is `required|integer|between:1,5` — already rejects
`0`, `6`, negative values, and decimals (an `integer` rule fails a `"4.7"`
input). Explicit boundary tests added (`ReviewTest::test_rating_boundary_values_are_rejected`).

## 7. Verified Service

Every `Review` row is, by construction, tied to a completed Oncall job —
so `ReviewResource` always serializes `verified_service: true`. This means
the review is linked to a completed service transaction recorded by Oncall
Philippines — **not** that Oncall independently verified every factual
claim in the review text (the wording is deliberately narrow, per spec
§13).

## 8. Editing / withdrawal policy

**Reviews stay immutable** (user-confirmed decision) — `ReviewPolicy::update()`
returns `false`, matching the pre-existing policy and its locked-in tests.
**Withdrawal was added** (new either way, since no soft-removal path
existed before): `ReviewService::withdraw()` — reviewer-only, only while
`Published`, sets `status = Withdrawn` and recalculates the reviewee's
reputation. Withdrawn reviews are excluded from public listings and
aggregates but never hard-deleted, preserving history for disputes/audit
(§16).

## 9. Review statuses

`Published` (default, counts toward aggregates and appears publicly),
`Hidden`/`Removed` (admin moderation, excluded from aggregates and public
listings), `Withdrawn` (reviewer-initiated, same exclusion). No
"under review" gate on normal submission — a valid review publishes
immediately, matching §17's "avoid unnecessary moderation bottlenecks."

## 10. Provider responses

`ReviewService::respond()` — only the reviewed provider (`reviewee_id`
match), only once (`response`/`responded_at` set together; a second
attempt is `409`), only while `ReviewSetting::provider_response_enabled`.
The response endpoint never touches `rating` — verified by
`ReviewResponseTest::test_provider_response_never_changes_the_customers_rating`.
Rendered distinctly labeled "Response from provider" everywhere it
appears (web and Flutter), never styled as another customer review.

## 11. Reporting

New `ReviewReport` model/service, **not** a retrofit of the existing
`UserReport` — that table is hard-wired to `Job`+`User` in both its schema
and its controller signature, and review-content report categories
(`HARASSMENT`, `SPAM`, `PERSONAL_INFORMATION`, `FALSE_OR_MISLEADING`,
`OFFENSIVE_CONTENT`, `THREAT`, `UNRELATED_CONTENT`, `OTHER` — matching spec
§19 exactly) are a distinct vocabulary from `UserReport`'s job/user-safety
categories. `ReviewReportService::report()` is idempotent per
`(review_id, reporter_id)` (`firstOrCreate` over the unique constraint) —
repeated/duplicate reporting from the same user is a no-op, not an error.
**Reporting never automatically hides or removes a review** — that's a
separate, explicit moderator action.

## 12. Moderation

`ReviewService::moderate()` (admin-gated via the `moderate-reviews` Gate) —
sets `status` to `Published`/`Hidden`/`Removed` (never `Withdrawn`, which
stays reviewer-only) and recalculates reputation. `ReviewReportService::resolve()`
(gated via `view-review-reports`) independently marks a report
`Resolved`/`Dismissed`. Both write `AuditLog` entries; `moderation_notes`
and any audit "notes" field are internal-only, never rendered on any
marketplace-facing page or API resource.

## 13. Moderation transparency

`ReviewReportResource` (admin/web-only) is the only place reporter identity
and moderation notes ever appear — `ReviewResource` (used everywhere a
marketplace user can reach) never includes either field. A
hidden/removed review simply stops appearing in public listings/aggregates
— there is no "this review is currently unavailable" placeholder needed
since hidden reviews are filtered out entirely rather than shown redacted.

## 14. Aggregation

New `App\Services\ProviderReputationService`:
- `recalculate(User $provider)` — the single authoritative aggregate query
  (`AVG`/`COUNT` over `Review::where('reviewee_id', ...)->where('status', Published)`),
  replacing the inline computation that used to live in `ReviewService::create()`.
  Writes `rating_cached`+`reviews_count` on `User` and
  `rating_cached`+`reviews_count`+`reputation_score` on `ProviderProfile`
  (the latter denormalized so `ProviderProfileResource` never needs an
  extra `User` query per search result). Called after create, withdraw,
  and every moderation action — so hidden/removed/withdrawn reviews are
  handled consistently everywhere (excluded from every aggregate, always).
- `summary(ProviderProfile $profile)` — the sanitized reputation payload
  (§29): `average_rating`, `rating_count`, `completed_services`,
  `verified_review_count` (always equal to `rating_count` — every review
  here is inherently verified), `rating_distribution` (percentage per
  star, computed fresh from published reviews).

## 15. Rating distribution & no-review state

`rating_distribution` is `{'1'..'5' => 0.0}` for a provider with zero
reviews — never fabricated. `average_rating` is `null`, not `0.0`, with
`rating_count: 0` — both the web `<x-ui.rating>` component and Flutter's
`RatingSummary` widget already distinguished "New" (no rating) from a
low numeric rating before Phase P; that behavior is preserved and now
driven by the same `reputation` payload everywhere.

## 16. Completed-service statistics

Unchanged: `completed_jobs_cached` is incremented exactly once, in
`JobService::transition()` when a job reaches `Completed` — never editable
by a provider, never touched by anything in the review/reputation code
path.

## 17. Search/ranking integration

`ProviderSearchService::sortComparators()`'s rating comparator now compares
`reputation_score` (falling back to `rating_cached` for a provider with no
score yet) instead of the raw average, in both the `'rating'`-sort and the
`recommended`/`nearest` tie-breaker position. `reputation_score` is a
simple **Bayesian average**: `(C·m + Σratings) / (C + n)` with a fixed
prior `m = 3.5` and minimum-votes constant `C = 5`, computed alongside
`recalculate()`. This keeps a single lucky 5-star review from
automatically outranking a provider with hundreds of consistently strong
reviews (§32/§33) — verified by
`ProviderReputationServiceTest::test_bayesian_reputation_score_keeps_a_single_five_star_review_from_outranking_many_strong_reviews`
and a matching search-endpoint regression test in `ProviderSearchTest`.
**The publicly displayed rating is always the honest arithmetic
`rating_cached` average** — `reputation_score` is never serialized in any
API resource or rendered anywhere; it exists solely inside the ranking
comparator.

## 18. Provider profile / card integration

`ProviderProfileResource` embeds the full `reputation` object (§29 shape)
alongside the existing `rating`/`completed_jobs` fields. Web
(`providers/show.blade.php`) and Flutter (`ProviderProfileScreen`) both
render: rating summary, rating-distribution bars, paginated review list
(newest/highest/lowest sort — whitelisted values only, never an arbitrary
client sort field), provider responses inline, a "Report" action per
review, and the honest "No reviews yet" empty state. `ProviderCard`/list
cards remain unchanged (already showed rating + completed count from
before Phase P) — no overload of the compact card per §31.

## 19. Notifications

Two new event keys in `config/notifications.php`: `review_received` (→
reviewee, on submission) and `review_response_received` (→ reviewer, on a
provider response). Both dispatched via the existing
`NotificationDispatcher`, following the exact call shape already used in
`JobService::transition()`/`DisputeService`, and both route to the
existing `job` screen (no new Flutter route or screen-whitelist entry
needed). Moderation/report-status changes do **not** notify — the spec
only requires it "where policy requires provider notice," and no such
requirement was identified as necessary for MVP.

## 20. Admin settings

New singleton `ReviewSetting` (mirrors `LocationSetting`): `reviews_enabled`,
`review_window_days`, `comment_required`, `max_comment_length`,
`provider_response_enabled`, `response_max_length`, `reviews_per_page`.
Managed at `/admin/settings/reviews`. Moderation queue at
`/admin/review-reports` (list/detail/resolve) plus a direct review-visibility
action at `PATCH /admin/reviews/{review}/moderate` — kept separate from
report-resolution since a moderator may act on a review with or without an
open report, and resolving a report never by itself changes visibility.

## 21. Authorization / permissions

`manage-review-settings`, `moderate-reviews`, `view-review-reports` — all
`$user->canAccessAdmin()`, matching every prior phase's settings/moderation
gate (ADR-001 has no separate Enforcement/Compliance sub-role).
Marketplace roles (`ServiceFinder`/`ServiceProvider`) and back-office roles
without admin (`Accounting`/`Budget`/`Cashier`) all fail this gate — tested
explicitly. Every admin review/report route is web-only by construction
(no `/api/v1` equivalent exists) — a mobile Sanctum token gets a plain 404,
tested the same way as every other Phase O/P admin surface.

## 22. Audit logging

`review.moderated` (before/after status; moderation notes stored only in
`after_json`, never in a free-text log line) and `review_report.resolved`
(before/after report status). `review_settings.updated` for settings
changes. No audit event ever contains raw private moderation
investigation text outside its own `after_json` field.

## 23. Performance / indexes

`reviews` composite index `(reviewee_id, status, created_at)` (replaces
the prior `(reviewee_id, created_at)` two-column index — added before the
old one was dropped, since MySQL refuses to drop an index still covering
the `reviewee_id` foreign key if it would leave that column
momentarily unindexed). `review_reports`: unique `(review_id, reporter_id)`
plus `(status, created_at)` for the moderation queue. `ProviderProfileResource`'s
reputation summary reads denormalized columns already on the loaded
`ProviderProfile` row — no extra query per search result.

## 24. Aggregate rebuild strategy

`php artisan reputation:recalculate` — loops every `ServiceProvider` user
and calls `ProviderReputationService::recalculate()`. Rebuilds
`rating_cached`/`reviews_count`/`reputation_score` from the authoritative
`reviews` table only; never modifies a review row itself (§59).

## 25. Security protections (verified via `tests/Feature/Review*Test.php`, `tests/Feature/ProviderReputationServiceTest.php`)

- Review without a completed service: blocked by `ReviewEligibilityService`
  + the DB-level `status === Completed` check.
- Spoofed reviewer/provider: reviewer/reviewee are always derived from
  `auth()->user()` and the job's own columns — never client input.
- Manipulated job ID: `JobPolicy`-gated participant membership, not merely
  route-model-binding existence.
- Editing another user's review: no edit path exists at all.
- Provider changing their own rating via a response: structurally
  impossible — the response endpoint only ever writes `response`/`responded_at`.
- Stored XSS: plain-text storage + existing Blade auto-escaping (no
  `{!! !!}` anywhere in review-related views) + `NoDirectContact` +
  length-limit validation on comments/responses.
- Moderation IDOR: `canAccessAdmin()` Gate on every moderation action,
  policy-checked.
- Reporter identity / internal notes leakage: confined to the admin-only
  `ReviewReportResource`; explicitly tested absent from every
  marketplace-facing response.
- Fake completed-service count: untouched, still solely
  `JobService::transition()`-driven.
- Client-supplied rating/reputation values: `rating_cached`/`reputation_score`
  are only ever written by `ProviderReputationService`.
- Notification deep-link bypass: routes through the existing `job` screen
  + `JobPolicy` authorization on load — unchanged mechanism, no new
  screen/whitelist entry introduced.

## 26. Future extensions

Explicitly deferred, per spec §55/§56: helpful-vote infrastructure, review
photo/video attachments. A confidence-aware "Most Helpful" sort was not
added for the same reason. A two-step completion-confirmation flow and
auto-completion timeout (§5–§7) were evaluated and deliberately not built
— see §2 above; a future phase could reconsider this specifically for
disputes/fraud-prevention reasons if that becomes a real product need,
independent of reviews.
