# PHASE I — Safety and Enforcement Workflow

Goal: implement/refine real server-enforced account discipline.

Stages:
1. Warning
2. Account Review
3. Temporary Restriction
4. Suspension

Users may view status/messages and receive notifications. They may not clear or change enforcement state.

Authorized web enforcement staff may review incidents, issue warnings, place under review, restrict, suspend, reactivate/remove restriction when permitted, and record reasons.

Define restriction effects centrally and enforce through middleware/policies/actions. Potential blocked actions include new requests, accepting jobs, messaging, withdrawals, or provider availability changes.

Audit all enforcement changes.

Test restricted/suspended users cannot bypass via direct requests and unrelated back-office roles cannot enforce unless permitted.

Stop when enforcement has real server-side effects, not just labels.
