<?php

namespace App\Enums;

enum ReviewReportCategory: string
{
    case Harassment = 'HARASSMENT';
    case Spam = 'SPAM';
    case PersonalInformation = 'PERSONAL_INFORMATION';
    case FalseOrMisleading = 'FALSE_OR_MISLEADING';
    case OffensiveContent = 'OFFENSIVE_CONTENT';
    case Threat = 'THREAT';
    case UnrelatedContent = 'UNRELATED_CONTENT';
    case Other = 'OTHER';
}
