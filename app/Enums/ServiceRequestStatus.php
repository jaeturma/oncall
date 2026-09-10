<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Requested = 'REQUESTED';
    case Searching = 'SEARCHING';
    case Accepted = 'ACCEPTED';
    case Cancelled = 'CANCELLED';
    case Expired = 'EXPIRED';
}
