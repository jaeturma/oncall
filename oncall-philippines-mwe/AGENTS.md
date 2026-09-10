# AGENTS.md — Oncall Philippines

These rules apply to Codex, Claude Code, Copilot, or any coding agent working on this project.

## Non-negotiable architecture

- Laravel 13
- Blade
- Tailwind CSS 4
- MySQL 8
- Prefer framework-native features before adding packages.
- Do not introduce React, Vue, Inertia, Livewire, WebSockets, Docker, payment gateways, maps, AI APIs, or third-party identity APIs unless explicitly approved.
- Avoid overengineering.
- Every phase must remain deployable.

## Naming

Use these product terms consistently:

- Customer = `Service Finder`
- Worker / professional = `Service Provider`
- Referral owner = `Sponsor`

Internal class/table naming may use `customer` only if migration compatibility requires it, but UI wording must prefer `Service Finder`.

## Public search

Landing page has only two primary selectors:

1. What help do you need?
2. Where do you need help?

Search supports:
- service OR broad category
- province

Results page may refine:
- specific service
- municipality/city

Guests:
- may search
- may view anonymized results
- may not view provider names or direct contact details
- may not request service

Verified Service Finders:
- may see provider identity according to permissions
- may request service

## Safety

Oncall's core message:

**Stay on Oncall. Stay protected.**

Never expose:
- phone
- email
- social media
- exact address
- direct payment details

before the booking stage permits it.

Enforcement workflow:

`Warning → Account Review → Temporary Restriction → Suspension`

Automated detection may FLAG but must not automatically prove a violation.

## Financial rules

- Sponsor relationship is single-level only.
- No multi-level/downline commission logic.
- Commission is created only from configured legitimate Oncall transactions.
- Never store a wallet as only a mutable balance field.
- Wallet must be derived from ledger entries.
- Never delete financial transactions to fix mistakes. Post reversal/adjustment entries.
- Withdrawal amount must be reserved immediately to prevent double withdrawal.

Withdrawal flow:

`Requested → Accounting Review → Budget Approval → For Disbursement → Disbursed → Completed`

## Theme

Theme is presentation only.

Do not:
- move business rules into theme JS
- rewrite routes just to match demo URLs
- copy fake/demo data into production seeders
- expose static contact information from theme cards

See `docs/THEME-INTEGRATION.md`.

## Testing requirement

Each phase must add feature tests for its core flow.
Do not proceed with a known red test unless documented as intentionally deferred.
