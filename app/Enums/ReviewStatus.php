<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Published = 'PUBLISHED';
    case Hidden = 'HIDDEN';
    case Removed = 'REMOVED';
    case Withdrawn = 'WITHDRAWN';
}
