<?php

namespace App\Enums;

enum SmsDeliveryStatus: string
{
    case Queued = 'QUEUED';
    case Sent = 'SENT';
    case Accepted = 'ACCEPTED';
    case Delivered = 'DELIVERED';
    case Failed = 'FAILED';
    case Rejected = 'REJECTED';
}
