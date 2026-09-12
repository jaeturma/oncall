# PHASE E — Prevent Back-Office Accounts From Using Mobile Login

Goal: prevent back-office-only users from obtaining marketplace mobile access.

Mobile allowed: customer, provider, sponsor/referrer.
Back-office-only examples: super_admin, admin, verifier, accounting, budget, cashier, enforcement/compliance, maintenance.

Mobile login must validate credentials, active status, mobile capability, restrictions, and token eligibility.

Back-office-only users should receive a controlled message such as: “Your account is authorized for the Oncall Philippines web administration portal only.” Do not expose unnecessary internals.

Support token issuance/revocation and current-device logout. Prepare cleanly for future logout-all/device management if appropriate.

Tests: customer/provider/sponsor succeed; admin/accounting/budget/cashier/verifier fail.

Stop when back-office-only accounts cannot obtain marketplace mobile access.
