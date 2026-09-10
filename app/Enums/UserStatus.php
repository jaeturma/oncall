<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'ACTIVE';
    case Warning = 'WARNING';
    case UnderReview = 'UNDER_REVIEW';
    case Restricted = 'RESTRICTED';
    case Suspended = 'SUSPENDED';
}
