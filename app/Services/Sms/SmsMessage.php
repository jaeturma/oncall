<?php

namespace App\Services\Sms;

use App\Enums\OtpPurpose;

/** A single outgoing SMS, independent of which provider ultimately sends it. */
final readonly class SmsMessage
{
    public function __construct(
        public string $mobile,
        public string $body,
        public string $messageType,
        public ?OtpPurpose $purpose = null,
    ) {}
}
