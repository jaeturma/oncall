<?php

namespace App\Enums;

enum ViolationSeverity: string
{
    case Low = 'LOW';
    case Moderate = 'MODERATE';
    case High = 'HIGH';
    case Critical = 'CRITICAL';
}
