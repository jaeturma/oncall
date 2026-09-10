<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Submitted = 'SUBMITTED';
    case UnderReview = 'UNDER_REVIEW';
    case Resolved = 'RESOLVED';
    case Dismissed = 'DISMISSED';
}
