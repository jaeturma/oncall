<?php

namespace Tests\Unit;

use App\Services\Sms\SmsUrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmsUrlValidatorTest extends TestCase
{
    #[DataProvider('unsafeUrls')]
    public function test_rejects_unsafe_destinations(string $url): void
    {
        $this->assertFalse(SmsUrlValidator::isSafe($url));
    }

    /** @return array<string, array{string}> */
    public static function unsafeUrls(): array
    {
        return [
            'loopback IPv4' => ['http://127.0.0.1/send'],
            'loopback hostname' => ['http://localhost/send'],
            'loopback IPv6' => ['http://[::1]/send'],
            'link-local' => ['http://169.254.169.254/latest/meta-data'],
            'private class A' => ['http://10.0.0.5/send'],
            'private class B' => ['http://172.16.0.5/send'],
            'private class C' => ['http://192.168.1.5/send'],
            'not a url' => ['not-a-url'],
            'missing scheme' => ['sms.example.com/send'],
            'javascript scheme' => ['javascript://alert(1)'],
        ];
    }

    public function test_accepts_a_well_formed_public_https_url(): void
    {
        $this->assertTrue(SmsUrlValidator::isSafe('https://93.184.216.34/send'));
    }

    public function test_rejects_a_literal_private_ip_even_without_dns(): void
    {
        $this->assertFalse(SmsUrlValidator::isSafe('https://192.168.1.1:8080/api/send'));
    }
}
