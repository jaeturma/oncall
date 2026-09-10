# Database Plan

## Core tables

### users
- id
- name
- email
- phone
- password
- status
- identity_verification_status
- email_verified_at
- phone_verified_at
- timestamps

### roles
Use a simple enum/role package only if needed.
Roles:
- SERVICE_FINDER
- SERVICE_PROVIDER
- ADMIN
- ACCOUNTING
- BUDGET
- CASHIER

### provinces
- id
- psgc_code nullable
- name

### municipalities
- id
- province_id
- psgc_code nullable
- name
- type (municipality/city)

### service_categories
- id
- name
- slug
- active
- sort_order

### services
- id
- service_category_id
- name
- slug
- active
- verification_level nullable

### provider_profiles
- id
- user_id
- province_id
- municipality_id
- bio nullable
- available_now
- service_radius_km nullable
- verification_status
- rating_cached nullable
- completed_jobs_cached default 0
- timestamps

### provider_services
- id
- provider_profile_id
- service_id
- experience_text nullable
- rate_type nullable
- rate_from nullable
- rate_to nullable
- active

### verification_records
- id
- user_id
- type
- status
- reviewed_by nullable
- reviewed_at nullable
- notes nullable

### provider_documents
- id
- user_id
- document_type
- private_path
- status
- reviewed_by nullable
- reviewed_at nullable
- expires_at nullable

### service_requests
- id
- service_finder_id
- requested_provider_id nullable
- service_id
- province_id
- municipality_id nullable
- title
- description nullable
- urgency
- needed_at nullable
- budget_min nullable
- budget_max nullable
- status
- timestamps

### jobs
- id
- service_request_id
- service_finder_id
- provider_id
- agreed_price nullable
- status
- accepted_at nullable
- on_the_way_at nullable
- started_at nullable
- completed_at nullable
- cancelled_at nullable
- timestamps

### job_status_logs
- id
- job_id
- from_status nullable
- to_status
- changed_by
- notes nullable
- timestamps

### reviews
- id
- job_id
- reviewer_id
- reviewee_id
- rating
- comment nullable
- timestamps

### user_reports
- id
- reporter_id
- reported_user_id
- job_id nullable
- category
- description
- status
- reviewed_by nullable
- reviewed_at nullable
- timestamps

### enforcement_cases
- id
- user_id
- related_job_id nullable
- related_report_id nullable
- violation_category
- severity
- status
- action
- starts_at nullable
- ends_at nullable
- handled_by nullable
- resolution nullable
- appeal_status nullable
- timestamps

## Sponsorship and finance

### account_types
- id
- name
- code
- registration_fee
- commission_type (fixed/percentage)
- commission_value
- requires_verification
- active
- timestamps

### user_sponsorships
- id
- user_id unique
- sponsor_user_id
- assigned_by
- assigned_at
- status
- notes nullable

### registration_fees
- id
- user_id
- account_type_id
- amount
- status
- assessed_by nullable
- assessed_at nullable
- paid_at nullable
- verified_by nullable
- verified_at nullable

### commissions
- id
- beneficiary_user_id
- sponsored_user_id
- source_type
- source_id
- commission_type
- commission_rate nullable
- gross_amount
- commission_amount
- status
- earned_at nullable
- available_at nullable
- approved_by nullable

### wallet_transactions
- id
- user_id
- type
- reference_type nullable
- reference_id nullable
- credit decimal default 0
- debit decimal default 0
- status
- description
- posted_at nullable
- timestamps

### withdrawals
- id
- withdrawal_no unique
- user_id
- amount
- payment_method
- account_name nullable
- account_reference nullable
- status
- requested_at
- accounting_reviewed_by nullable
- accounting_reviewed_at nullable
- budget_approved_by nullable
- budget_approved_at nullable
- disbursed_by nullable
- disbursed_at nullable
- disbursement_reference nullable
- proof_attachment nullable
- remarks nullable

### audit_logs
- id
- actor_id nullable
- event
- subject_type
- subject_id
- before_json nullable
- after_json nullable
- ip_address nullable
- user_agent nullable
- timestamps
