<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Security/PasswordResetToken.php';

final class PasswordResetTokenTest extends TestCase
{
    public function testIssuedTokenIsStoredOnlyAsItsDigest(): void
    {
        $issued = PasswordResetToken::issue();

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $issued['token']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $issued['digest']);
        self::assertNotSame($issued['token'], $issued['digest']);
        self::assertSame($issued['digest'], PasswordResetToken::digest($issued['token']));
    }

    public function testMalformedTokenIsRejectedBeforeDatabaseLookup(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PasswordResetToken::digest('not-a-reset-token');
    }
}
