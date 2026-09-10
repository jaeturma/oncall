# Minimal Architecture

## Layers

### HTTP
- Controllers
- Form Requests
- Middleware
- Policies

### Domain / Application
Use small service classes only where logic is shared or financial/safety-sensitive.

Suggested:
- ProviderSearchService
- ServiceRequestService
- BookingService
- VerificationService
- EnforcementService
- RegistrationFeeService
- CommissionService
- WalletLedgerService
- WithdrawalService

### Data
Eloquent models + MySQL.

## Suggested modules

- Users
- Roles
- Locations
- Service Catalog
- Provider Profiles
- Verification
- Provider Availability
- Search
- Service Requests
- Jobs
- Ratings
- Reports
- Safety / Enforcement
- Account Types
- Sponsorship
- Registration Fees
- Commissions
- Wallet Ledger
- Withdrawals
- Finance Workflow
- Audit Logs

## Do not add yet

- real-time GPS
- public provider phone directory
- escrow
- wallet top-up
- subscription plans
- multi-level commissions
- live chat server
- mobile app API
- AI matching
- external identity/KYC integrations
