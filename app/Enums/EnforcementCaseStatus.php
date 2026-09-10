<?php

namespace App\Enums;

enum EnforcementCaseStatus: string
{
    case Open = 'OPEN';
    case UnderReview = 'UNDER_REVIEW';
    case Restricted = 'RESTRICTED';
    case Suspended = 'SUSPENDED';
    case Resolved = 'RESOLVED';
}
