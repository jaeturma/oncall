<?php

namespace App\Enums;

enum JobMessageType: string
{
    case Message = 'MESSAGE';
    case Confirmation = 'CONFIRMATION';
    case Evidence = 'EVIDENCE';
}
