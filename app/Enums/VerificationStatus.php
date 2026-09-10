<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Pending = 'PENDING';
    case Submitted = 'SUBMITTED';
    case Verified = 'VERIFIED';
    case Rejected = 'REJECTED';
    case Expired = 'EXPIRED';
}
