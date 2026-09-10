<?php

namespace App\Enums;

enum AppealStatus: string
{
    case None = 'NONE';
    case Requested = 'REQUESTED';
    case UnderReview = 'UNDER_REVIEW';
    case Approved = 'APPROVED';
    case Denied = 'DENIED';
}
