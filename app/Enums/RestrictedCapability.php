<?php

namespace App\Enums;

enum RestrictedCapability: string
{
    case RequestService = 'REQUEST_SERVICE';
    case AcceptJobs = 'ACCEPT_JOBS';
    case Messaging = 'MESSAGING';
    case ContactReveal = 'CONTACT_REVEAL';
    case NewBookings = 'NEW_BOOKINGS';
    case Withdrawals = 'WITHDRAWALS';
    case AvailabilityChanges = 'AVAILABILITY_CHANGES';
    case FullAccountAccess = 'FULL_ACCOUNT_ACCESS';
}
