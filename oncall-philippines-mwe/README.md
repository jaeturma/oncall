# Oncall Philippines — Minimal Working Environment

Laravel 13 + Blade + Tailwind CSS 4

This package is the working environment / build guide for a fast MVP implementation of **Oncall Philippines**.

## Core product principle

**Choose what you need. Choose where you are. See who's available.**

The system connects a **Service Finder** with an available **Service Provider** while keeping identity, contact, booking, transaction, safety, and audit activity inside the platform.

## MVP technology

- Laravel 13
- PHP 8.4+
- Blade
- Tailwind CSS 4
- MySQL 8
- Database queue for MVP
- Database cache or Redis
- Laravel authentication
- Private storage for IDs / credentials
- Database notifications initially
- PHPUnit or Pest for feature tests

## Theme integration

Place the existing theme folder inside:

`theme-source/`

Then follow `docs/THEME-INTEGRATION.md`.

**Do not rewrite business logic to match the theme.**
The theme is presentation-only. Reuse its visual system, components, assets, spacing, typography, and layouts while keeping Laravel routes, controllers, policies, services, models, and validation independent.

## Recommended build order

1. WP-00 Foundation
2. WP-01 Auth & Users
3. WP-02 Services & Locations
4. WP-03 Provider Profiles
5. WP-04 Identity Verification
6. WP-05 Service Finder
7. WP-06 Service Requests
8. WP-07 Booking & Jobs
9. WP-08 Trust & Communication
10. WP-09 Safety & Enforcement
11. WP-10 Ratings & Reports
12. WP-11 Administration
13. WP-12 MVP Testing
14. WP-13 Account Types & Registration Fees
15. WP-14 Sponsorship / Referral
16. WP-15 Commission Engine
17. WP-16 Wallet Ledger
18. WP-17 Withdrawal / Cashout
19. WP-18 Accounting / Budget / Cashier
20. WP-19 Financial Reports & Audit

## MVP launch definition

The MVP is launchable when:

- Guest can search by service + province.
- Guest sees anonymized providers.
- Registered verified Service Finder can request a provider.
- Provider can accept/decline and progress a job.
- Contact remains protected until a confirmed booking.
- Ratings, reports, verification, safety enforcement, sponsorship, commissions, wallet ledger, and cashout workflow are operational.
- Admin can audit all critical actions.
