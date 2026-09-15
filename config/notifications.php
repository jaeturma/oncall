<?php

use App\Enums\NotificationCategory;

return [

    /*
    |--------------------------------------------------------------------------
    | Deep-link / navigation whitelist (Phase M Step 4 / Step 26)
    |--------------------------------------------------------------------------
    |
    | Every notification's `target.screen` must be one of these keys — see
    | NotificationCatalog::resolveTarget(). Flutter independently re-validates
    | against its own copy of this whitelist before navigating; this list
    | only controls what Laravel is willing to put in a payload, it is not
    | itself the client-side trust boundary. `true` means the screen
    | requires a `target.id`.
    */
    'screens' => [
        'service_request' => true,
        'job' => true,
        'conversation' => true,
        'wallet' => false,
        'withdrawal' => false,
        'verification' => false,
        'notifications' => false,
        'account_status' => false,
        'sponsored_users' => false,
        'enforcement_case' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event catalog (Phase M Step 2 / Step 14)
    |--------------------------------------------------------------------------
    |
    | The single source of truth business code dispatches against via
    | NotificationDispatcher::dispatch(). Each event declares: its
    | preference category, whether it can be disabled at all (`mandatory` —
    | Step 21), whether it is eligible for SMS fallback (Step 33 — the admin
    | must still opt each one in via NotificationSetting), and the safe
    | application-default title/body used when no admin template exists or
    | the admin template is disabled/invalid (Step 25). `{{placeholder}}`
    | tokens are the only ones NotificationTemplateService will substitute —
    | anything not listed in `placeholders` is rejected by SafeNotificationTemplate
    | if an admin tries to use it.
    */
    'events' => [

        'service_request_created' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['customer_name', 'service_name'],
            'default_title' => 'New service request',
            'default_body' => '{{customer_name}} requested "{{service_name}}".',
        ],
        'service_request_accepted' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['provider_name', 'service_name', 'amount'],
            'default_title' => 'Request accepted — booking confirmed',
            'default_body' => '{{provider_name}} accepted "{{service_name}}" at PHP {{amount}}.',
        ],
        'service_request_declined' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['service_name'],
            'default_title' => 'Provider declined your request',
            'default_body' => 'Your request "{{service_name}}" is open again — you can request another provider.',
        ],
        'provider_on_the_way' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['actor_name'],
            'default_title' => 'Booking updated: On The Way',
            'default_body' => '{{actor_name}} set the job to "On The Way".',
        ],
        'service_started' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['actor_name'],
            'default_title' => 'Booking updated: In Progress',
            'default_body' => '{{actor_name}} set the job to "In Progress".',
        ],
        'service_completed' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['actor_name'],
            'default_title' => 'Booking updated: Completed',
            'default_body' => '{{actor_name}} set the job to "Completed".',
        ],
        'service_cancelled' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['actor_name'],
            'default_title' => 'Booking updated: Cancelled',
            'default_body' => '{{actor_name}} set the job to "Cancelled".',
        ],
        'job_status_changed' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['actor_name', 'status'],
            'default_title' => 'Booking updated: {{status}}',
            'default_body' => '{{actor_name}} set the job to "{{status}}".',
        ],
        'dispute_opened' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['actor_name'],
            'default_title' => 'A dispute was opened on your job',
            'default_body' => '{{actor_name}} opened a dispute. The job payment is frozen until an admin resolves it.',
        ],
        'dispute_resolved' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['status', 'resolution'],
            'default_title' => 'Dispute resolved: {{status}}',
            'default_body' => '{{resolution}}',
        ],
        'review_received' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['reviewer_name', 'rating'],
            'default_title' => 'You received a new review',
            'default_body' => '{{reviewer_name}} left a {{rating}}-star review.',
        ],
        'review_response_received' => [
            'category' => NotificationCategory::ServiceUpdates,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['provider_name'],
            'default_title' => 'The provider responded to your review',
            'default_body' => '{{provider_name}} posted a response to your review.',
        ],
        'new_message' => [
            'category' => NotificationCategory::Messaging,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['sender_name'],
            'default_title' => 'New message',
            'default_body' => 'You have a new message from {{sender_name}}.',
        ],
        'verification_approved' => [
            'category' => NotificationCategory::Verification,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => [],
            'default_title' => 'Identity verification approved',
            'default_body' => 'Your identity is verified. A badge now appears on your profile.',
        ],
        'verification_rejected' => [
            'category' => NotificationCategory::Verification,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => [],
            'default_title' => 'Identity verification not approved',
            'default_body' => 'Your latest document was not approved. Review the notes and submit again.',
        ],
        'verification_revoked' => [
            'category' => NotificationCategory::Verification,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => [],
            'default_title' => 'Identity verification revoked',
            'default_body' => 'Your identity verification was revoked. Review the notes and submit a new document.',
        ],
        'job_payment_confirmed' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['amount'],
            'default_title' => 'Payment confirmed by the Service Finder',
            'default_body' => 'PHP {{amount}} is pending Oncall release into your wallet.',
        ],
        'job_payment_released' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['amount'],
            'default_title' => 'Job earning released to your wallet',
            'default_body' => 'PHP {{amount}} is now available to withdraw.',
        ],
        'withdrawal_submitted' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => ['amount'],
            'default_title' => 'Withdrawal request received',
            'default_body' => 'Your PHP {{amount}} withdrawal request was received and is under accounting review.',
        ],
        'withdrawal_accounting_approved' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => ['amount'],
            'default_title' => 'Withdrawal update',
            'default_body' => 'Your PHP {{amount}} withdrawal passed accounting review and is now with Budget.',
        ],
        'withdrawal_budget_approved' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => ['amount'],
            'default_title' => 'Withdrawal update',
            'default_body' => 'Your PHP {{amount}} withdrawal was approved and is now queued for disbursement.',
        ],
        'withdrawal_disbursed' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => ['amount'],
            'default_title' => 'Withdrawal disbursed',
            'default_body' => 'Your PHP {{amount}} withdrawal has been disbursed.',
        ],
        'withdrawal_rejected' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => ['amount'],
            'default_title' => 'Withdrawal rejected',
            'default_body' => 'Your PHP {{amount}} withdrawal was rejected.',
        ],
        'withdrawal_returned' => [
            'category' => NotificationCategory::Wallet,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => ['amount'],
            'default_title' => 'Withdrawal returned for correction',
            'default_body' => 'Your PHP {{amount}} withdrawal was returned for correction — the amount is back in your wallet.',
        ],
        'sponsor_commission_pending' => [
            'category' => NotificationCategory::Sponsor,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['sponsored_name', 'amount'],
            'default_title' => 'Sponsor commission pending',
            'default_body' => 'A PHP {{amount}} commission from {{sponsored_name}} is pending release.',
        ],
        'sponsor_commission_posted' => [
            'category' => NotificationCategory::Sponsor,
            'mandatory' => false,
            'sms_fallback' => false,
            'placeholders' => ['sponsored_name', 'amount'],
            'default_title' => 'Sponsor commission released',
            'default_body' => 'PHP {{amount}} from {{sponsored_name}} is now available in your wallet.',
        ],
        'enforcement_warning' => [
            'category' => NotificationCategory::Enforcement,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => [],
            'default_title' => 'Account warning issued',
            'default_body' => 'There is an important update regarding your Oncall Philippines account. Open the app for details.',
        ],
        'account_under_review' => [
            'category' => NotificationCategory::Enforcement,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => [],
            'default_title' => 'Account under review',
            'default_body' => 'There is an important update regarding your Oncall Philippines account. Open the app for details.',
        ],
        'account_restricted' => [
            'category' => NotificationCategory::Account,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => [],
            'default_title' => 'Account restricted',
            'default_body' => 'There is an important update regarding your Oncall Philippines account. Open the app for details.',
        ],
        'account_suspended' => [
            'category' => NotificationCategory::Account,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => [],
            'default_title' => 'Account suspended',
            'default_body' => 'There is an important update regarding your Oncall Philippines account. Open the app for details.',
        ],
        'account_reactivated' => [
            'category' => NotificationCategory::Account,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => [],
            'default_title' => 'Account reactivated',
            'default_body' => 'Your Oncall Philippines account is active again.',
        ],
        'enforcement_appeal_reviewed' => [
            'category' => NotificationCategory::Enforcement,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => ['status', 'resolution'],
            'default_title' => 'Appeal {{status}}',
            'default_body' => '{{resolution}}',
        ],
        'security_mobile_verified' => [
            'category' => NotificationCategory::Security,
            'mandatory' => true,
            'sms_fallback' => false,
            'placeholders' => [],
            'default_title' => 'Mobile number verified',
            'default_body' => 'Your mobile number is verified.',
        ],
        'security_mobile_changed' => [
            'category' => NotificationCategory::Security,
            'mandatory' => true,
            'sms_fallback' => true,
            'placeholders' => [],
            'default_title' => 'Mobile number changed',
            'default_body' => 'Your account mobile number was changed. If this was not you, contact support immediately.',
        ],
    ],
];
