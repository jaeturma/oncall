<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Database = 'DATABASE';
    case Push = 'PUSH';
    case Sms = 'SMS';
}
