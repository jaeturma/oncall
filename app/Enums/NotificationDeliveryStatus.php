<?php

namespace App\Enums;

enum NotificationDeliveryStatus: string
{
    case Queued = 'QUEUED';
    case Sent = 'SENT';
    case Accepted = 'ACCEPTED';
    case Delivered = 'DELIVERED';
    case Failed = 'FAILED';
    case InvalidToken = 'INVALID_TOKEN';
    case Skipped = 'SKIPPED';
}
