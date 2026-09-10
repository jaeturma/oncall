<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Open = 'OPEN';
    case UnderReview = 'UNDER_REVIEW';
    case Upheld = 'UPHELD';
    case Rejected = 'REJECTED';
    case PartiallyUpheld = 'PARTIALLY_UPHELD';
    case Withdrawn = 'WITHDRAWN';

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::UnderReview], true);
    }
}
