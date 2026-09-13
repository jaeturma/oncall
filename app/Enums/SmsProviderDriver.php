<?php

namespace App\Enums;

/**
 * Which `SmsProviderInterface` implementation a `sms_providers` row uses.
 * Kept as its own enum (rather than a free-text string) so `SmsManager`
 * can resolve a driver to a class in one place; adding a real provider
 * (Semaphore, Twilio, ...) later means adding a case here plus its class,
 * not touching the schema.
 */
enum SmsProviderDriver: string
{
    case GenericHttp = 'GENERIC_HTTP';
}
