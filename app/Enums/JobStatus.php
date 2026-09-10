<?php

namespace App\Enums;

enum JobStatus: string
{
    case Accepted = 'ACCEPTED';
    case OnTheWay = 'ON_THE_WAY';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case Disputed = 'DISPUTED';
}
