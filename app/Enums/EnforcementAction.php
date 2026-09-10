<?php

namespace App\Enums;

enum EnforcementAction: string
{
    case Warning = 'WARNING';
    case AccountReview = 'ACCOUNT_REVIEW';
    case TemporaryRestriction = 'TEMPORARY_RESTRICTION';
    case Suspension = 'SUSPENSION';
}
