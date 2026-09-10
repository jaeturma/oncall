<?php

namespace App\Enums;

enum CommissionStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Available = 'AVAILABLE';
    case Reversed = 'REVERSED';
}
