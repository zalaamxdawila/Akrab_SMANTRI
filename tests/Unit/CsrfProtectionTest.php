<?php

use PHPUnit\Framework\TestCase;

final class CsrfProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsGeneratedAndReusedWithinSession(): void
    {
        $first = csrfToken();
        $second = csrfToken();

        self::assertSame($first, $second);
        self::assertSame(64, strlen($first));
    }

    public function testValidTokenIsAccepted(): void
    {
        $token = csrfToken();

        self::assertTrue(csrfTokenIsValid($token));
    }

    public function testMissingOrInvalidTokenIsRejected(): void
    {
        csrfToken();

        self::assertFalse(csrfTokenIsValid(null));
        self::assertFalse(csrfTokenIsValid('invalid'));
    }

    public function testInputContainsEscapedSessionToken(): void
    {
        $token = csrfToken();

        self::assertSame(
            '<input type="hidden" name="_csrf" value="' . $token . '">',
            csrfInput()
        );
    }
}
