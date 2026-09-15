<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Requested = 'REQUESTED';
    case UnderReview = 'UNDER_REVIEW';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Processing = 'PROCESSING';
    case Completed = 'COMPLETED';
    case Failed = 'FAILED';
}
