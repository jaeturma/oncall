<?php

return [
    'service_requests' => [
        'require_identity_verification' => env('ONCALL_REQUIRE_IDENTITY_VERIFICATION', true),
    ],
];
