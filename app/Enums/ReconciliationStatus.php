<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Open = 'OPEN';
    case Resolved = 'RESOLVED';
}
