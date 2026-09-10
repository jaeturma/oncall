<?php

return [
    'service_requests' => [
        'require_identity_verification' => env('ONCALL_REQUIRE_IDENTITY_VERIFICATION', true),
    ],

    'platform' => [
        // Default Oncall cut of a completed job's agreed price, as a percentage.
        // An account type may override this with its own platform_commission_percent.
        'commission_percent' => env('ONCALL_PLATFORM_COMMISSION_PERCENT', 15),
    ],
];
