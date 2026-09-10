# MVP Testing Checklist

## Public search
- guest can search service + province
- result is anonymized
- guest cannot request service
- municipality refinement works
- service refinement works
- no personal contact leaks

## Authentication
- register
- login/logout
- sponsor recorded
- admin sponsor assignment audited

## Verification
- user uploads private document
- unauthorized users cannot access document
- admin approve/reject
- verified state gates service request

## Provider
- provider services
- availability toggle
- inactive/unverified provider excluded where required

## Request/job
- verified finder creates request
- provider accepts/declines
- only assigned parties can see booking details
- status transitions validated
- completion records timestamps

## Safety
- report user
- warning
- account review
- temporary restriction
- suspension
- capability restrictions enforced
- audit trail generated

## Finance
- account type fee assigned
- fee verification triggers correct commission
- percentage and fixed commission tested
- no duplicate commission
- wallet ledger running balance correct
- reversal restores financial truth
- withdrawal reserves amount
- double-withdrawal prevented
- accounting → budget → cashier order enforced
- unauthorized role cannot approve stage
- disbursement proof/reference recorded
