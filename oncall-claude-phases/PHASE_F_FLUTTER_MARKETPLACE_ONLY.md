# PHASE F — Flutter App: Marketplace Users Only

Goal: ensure `mobile/oncall_mobile/` supports only customers, providers, and sponsors/referrers.

Customer navigation: Home, Requests, Messages, Wallet, Account.
Provider navigation: Home, Jobs, Messages, Wallet, Account.
Sponsor functions may be secondary under Account.

Customer features may include login/register, profile, service finder, provider results/profile, service request/history, messaging, notifications, wallet, cashout request, sponsored users, safety/reporting, and verification submission/status.

Provider features may include provider profile, offered services, availability, incoming requests, accept/reject, active jobs, allowed status updates, messaging, earnings/wallet, cashout request, sponsored users, and verification submission/status.

Never create admin/accounting/budget/cashier/verification-approval/enforcement-admin/settings screens or repositories.

Flutter uses only marketplace `/api/v1` endpoints. Do not use a WebView.

If Flutter exists, run:
```bash
flutter analyze
flutter test
```

Stop when Flutter contains only marketplace functionality.
