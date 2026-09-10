<?php

namespace App\Enums;

enum ServiceUrgency: string
{
    case Immediate = 'IMMEDIATE';
    case SameDay = 'SAME_DAY';
    case Scheduled = 'SCHEDULED';
}
