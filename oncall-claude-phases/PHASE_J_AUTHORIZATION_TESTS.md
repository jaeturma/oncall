# PHASE J — Authorization Test Suite

Goal: prove web/mobile role separation with route-level feature tests.

Required scenarios at minimum:
1. Customer can log into mobile API.
2. Provider can log into mobile API.
3. Sponsor can use allowed sponsor features.
4. Admin-only cannot use mobile login.
5. Accounting-only cannot use mobile login.
6. Budget-only cannot use mobile login.
7. Cashier-only cannot use mobile login.
8. Customer cannot access `/admin`.
9. Provider cannot access `/admin`.
10. Sponsor cannot access `/admin`.
11. Accounting cannot perform budget approval.
12. Budget cannot perform cashier disbursement.
13. Cashier cannot perform accounting approval.
14. Mobile API cannot approve cashout.
15. Mobile API cannot approve verification.
16. Mobile API cannot suspend accounts.
17. Restricted user cannot perform blocked actions.
18. Suspended user cannot perform blocked actions.
19. Guest cannot obtain private provider contact details.
20. API resources do not leak internal admin notes.

Prefer tests exercising actual routes/middleware, not only helpers.

Run `php artisan test` and fix regressions caused by these changes.

Stop when the role-separation suite passes.
