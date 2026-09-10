<?php

namespace App\Enums;

enum ReportCategory: string
{
    case OffPlatformContact = 'OFF_PLATFORM_CONTACT';
    case Harassment = 'HARASSMENT';
    case Fraud = 'FRAUD';
    case UnsafeBehavior = 'UNSAFE_BEHAVIOR';
    case NoShow = 'NO_SHOW';
    case Other = 'OTHER';
}
