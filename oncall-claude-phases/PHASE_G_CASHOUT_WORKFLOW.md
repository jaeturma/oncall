# PHASE G — Cashout Workflow

Goal: implement/refine mobile request/status with web-only approval.

Flow: User Request → Accounting Review → Budget Approval → Cashier Disbursement → User sees final status.

Suggested statuses: requested, accounting_review, accounting_approved, budget_review, budget_approved, cashier_processing, disbursed, rejected, cancelled. Adapt to existing states.

Mobile may create/view/cancel where permitted. Mobile may not approve, disburse, edit ledger, or alter amount after approval.

Use a wallet transaction ledger; never silently edit balances. Maintain auditable linkage between withdrawal and ledger records.

Permissions:
- Accounting: review/approve/reject accounting stage
- Budget: act only after accounting approval
- Cashier: disburse only after budget approval

Audit actor, stage, decision, timestamp, notes/reason, and reference where applicable.

Test stage skipping and unauthorized actions.

Stop when cashout transitions are role-separated and server-enforced.
