# Business Rules

## Search and privacy

1. Guest may search providers.
2. Guest results must be anonymized.
3. Guest cannot directly connect to a provider.
4. Provider direct contact is never publicly exposed.
5. Exact home/service addresses must be disclosed only when operationally necessary.
6. A verified Service Finder may request a provider.
7. Provider availability must be explicit and revocable.

## Search ranking MVP

Order primarily by:
1. active / approved
2. available now
3. exact municipality if selected
4. province
5. service match
6. verification
7. rating
8. completed jobs
9. distance if reliable coordinates exist

Do not fake distance if coordinates are unavailable.

## Safety policy

Core message:

**Stay on Oncall. Stay protected.**

Oncall may be unable to help with disputes or incidents when contact, booking, payment, or transactions are deliberately taken outside platform monitoring.

Applies equally to Service Finders and Service Providers.

### Enforcement states

- ACTIVE
- WARNING
- UNDER_REVIEW
- RESTRICTED
- SUSPENDED

### Enforcement progression

`Warning → Account Review → Temporary Restriction → Suspension`

Admin review is required for enforcement except explicitly defined automatic access safeguards.

Restriction can target capabilities:
- request service
- accept jobs
- messaging
- contact reveal
- new bookings
- full account access

## Sponsorship

1. New registration may identify an existing registered user or Admin as sponsor/reference person.
2. Each user normally has one sponsor.
3. Sponsorship is single-level.
4. Sponsor change must be authorized and audited.
5. Sponsor sees users directly sponsored by them.
6. No commission from recruitment chains.

## Account type & fee

Admin configures:
- account type
- registration fee
- sponsor commission type
- sponsor commission value
- verification requirement
- active state

## Commission

Sponsor commission is created only when configured trigger is satisfied, e.g. registration fee verified.

Statuses:
- PENDING
- APPROVED
- AVAILABLE
- REVERSED

## Wallet

Ledger is the financial source of truth.

Suggested ledger entry types:
- COMMISSION
- WITHDRAWAL_RESERVE
- WITHDRAWAL
- REVERSAL
- ADJUSTMENT
- REFUND

Never silently change or delete posted entries.

## Withdrawal

Flow:

REQUESTED
→ ACCOUNTING_REVIEW
→ BUDGET_APPROVAL
→ FOR_DISBURSEMENT
→ DISBURSED
→ COMPLETED

Other terminal/intermediate states:
- RETURNED
- REJECTED
- CANCELLED

The withdrawal amount is reserved as soon as the request is accepted by the system.
