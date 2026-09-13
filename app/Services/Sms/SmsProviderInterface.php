<?php

namespace App\Services\Sms;

/**
 * Every SMS provider driver implements this. `SmsManager` depends only on
 * this interface, never a concrete provider, so application code never
 * knows or cares which provider is currently active.
 */
interface SmsProviderInterface
{
    public function send(SmsMessage $message): SmsResult;

    public function testConnection(string $testMobile): SmsResult;
}
