<?php

namespace App\Enums;

enum CommissionType: string
{
    case None = 'NONE';
    case Fixed = 'FIXED';
    case Percentage = 'PERCENTAGE';
}
