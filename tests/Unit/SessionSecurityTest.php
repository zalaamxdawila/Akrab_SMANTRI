<?php

use PHPUnit\Framework\TestCase;

final class SessionSecurityTest extends TestCase
{
    public function testProductionCookiePolicyIsSecure(): void
    {
        $options = sessionCookieOptions('production');

        self::assertTrue($options['secure']);
        self::assertTrue($options['httponly']);
        self::assertSame('Lax', $options['samesite']);
        self::assertSame('/', $options['path']);
    }

    public function testTestingCookiePolicyCanRunWithoutHttps(): void
    {
        $options = sessionCookieOptions('testing');

        self::assertFalse($options['secure']);
        self::assertTrue($options['httponly']);
    }

    public function testIdleSessionExpires(): void
    {
        $session = [
            '_created_at' => 1_000,
            '_last_activity' => 1_100,
        ];

        self::assertTrue(sessionHasExpired($session, 1_401, 300, 3_600));
    }

    public function testAbsoluteSessionExpires(): void
    {
        $session = [
            '_created_at' => 1_000,
            '_last_activity' => 1_250,
        ];

        self::assertTrue(sessionHasExpired($session, 4_601, 600, 3_600));
    }

    public function testActiveSessionDoesNotExpire(): void
    {
        $session = [
            '_created_at' => 1_000,
            '_last_activity' => 1_250,
        ];

        self::assertFalse(sessionHasExpired($session, 1_300, 600, 3_600));
    }
}
