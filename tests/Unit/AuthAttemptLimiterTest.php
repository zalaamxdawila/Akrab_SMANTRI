<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Security/AuthAttemptLimiter.php';

final class AuthAttemptLimiterTest extends TestCase
{
    public function testBucketIsBlockedAtLimitAndRecoversAfterWindow(): void
    {
        $session = [];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $now = 1000 + $attempt;
            self::assertTrue(AuthAttemptLimiter::allows($session, 'passkey', $now, 5, 900));
            AuthAttemptLimiter::record($session, 'passkey', $now, 900);
        }

        self::assertFalse(AuthAttemptLimiter::allows($session, 'passkey', 1005, 5, 900));
        self::assertTrue(AuthAttemptLimiter::allows($session, 'passkey', 1905, 5, 900));
    }

    public function testSuccessfulAuthenticationCanClearOnlyItsOwnBucket(): void
    {
        $session = [];
        AuthAttemptLimiter::record($session, 'passkey', 1000);
        AuthAttemptLimiter::record($session, 'password-reset', 1000);

        AuthAttemptLimiter::clear($session, 'passkey');

        self::assertTrue(AuthAttemptLimiter::allows($session, 'passkey', 1001, 1));
        self::assertFalse(AuthAttemptLimiter::allows($session, 'password-reset', 1001, 1));
    }
}
