# Start Here — Coding Agent Prompt

Build **Oncall Philippines** using Laravel 13, Blade, Tailwind CSS 4, and MySQL 8.

Read first:
1. `AGENTS.md`
2. `docs/PROJECT.md`
3. `docs/RULES.md`
4. `docs/ARCHITECTURE.md`
5. `docs/DATABASE.md`
6. `docs/THEME-INTEGRATION.md`

Then execute work phases in numeric order under `docs/phases/`.

Important:
- Do not skip tests.
- Do not add unapproved frameworks/packages.
- Do not expose guest provider identities.
- Keep direct contact gated.
- Implement safety enforcement exactly:
  Warning → Account Review → Temporary Restriction → Suspension
- Sponsorship is direct/single-level only.
- Wallet uses ledger entries.
- Withdrawal requires Accounting → Budget → Cashier.
- Theme is presentation-only.

After each phase:
- run formatter
- run tests
- report changed files
- report migrations
- report any unresolved risk
- stop if a destructive migration or architecture change is required
