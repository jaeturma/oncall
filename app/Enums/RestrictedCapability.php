<?php

namespace App\Enums;

enum RestrictedCapability: string
{
    case RequestService = 'REQUEST_SERVICE';
    case AcceptJobs = 'ACCEPT_JOBS';
    case Messaging = 'MESSAGING';
    case ContactReveal = 'CONTACT_REVEAL';
    case NewBookings = 'NEW_BOOKINGS';
    case FullAccountAccess = 'FULL_ACCOUNT_ACCESS';
}
