<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthFlowHardeningTest extends TestCase
{
    public function testPasskeyLoginHonorsCentralAuthenticationPolicy(): void
    {
        $contents = file_get_contents(
            dirname(__DIR__, 2) . '/superadmin_passkey.php'
        );

        self::assertNotFalse($contents);
        self::assertStringContainsString('userCanAuthenticate($superadmin)', $contents);
        self::assertStringContainsString('regenerateAuthenticatedSession()', $contents);
        self::assertStringContainsString('credential_id = ? AND user_id = ?', $contents);
        self::assertStringContainsString(
            'SELECT id, public_key, sign_count FROM webauthn_credentials',
            $contents
        );
        self::assertStringNotContainsString(
            'SELECT * FROM webauthn_credentials',
            $contents
        );
        self::assertStringContainsString("unset(\$_SESSION['webauthn_challenge'])", $contents);
        self::assertStringContainsString('$cred[\'sign_count\']', $contents);
        self::assertStringContainsString('getSignatureCounter()', $contents);
        self::assertStringNotContainsString("'discouraged'", $contents);
        self::assertStringContainsString("20, true, 'required'", $contents);
    }

    public function testPasswordResetUsesOnlyHashedTokensAtRest(): void
    {
        $root = dirname(__DIR__, 2);
        $schema = file_get_contents($root . '/database/schema.sql');
        $migration = file_get_contents(
            $root . '/database/migrations/014_hash_password_reset_tokens.php'
        );
        $reset = file_get_contents($root . '/reset_password.php');
        $processor = file_get_contents($root . '/superadmin/process_reset_request.php');
        $dashboard = file_get_contents($root . '/superadmin/dashboard.php');

        self::assertStringContainsString('token_hash CHAR(64)', $schema);
        self::assertStringNotContainsString('token CHAR(64)', $schema);
        self::assertStringContainsString('SHA2(token, 256)', $migration);
        self::assertStringContainsString('DROP COLUMN token', $migration);
        self::assertStringContainsString('PasswordResetToken::digest($token)', $reset);
        self::assertStringContainsString("PasswordResetToken::issue()", $processor);
        self::assertStringNotContainsString('p.token,', $dashboard);
        self::assertStringNotContainsString('$req[\'token\']', $dashboard);
        self::assertMatchesRegularExpression(
            "/WHERE p\\.status = 'pending' ORDER BY p\\.created_at ASC LIMIT 100/",
            $dashboard
        );
    }

    public function testReleasePackagesOnlyTheRequiredManualDependency(): void
    {
        $include = file_get_contents(
            dirname(__DIR__, 2) . '/deployment/include.txt'
        );

        self::assertMatchesRegularExpression(
            '/^vendor_manual\/WebAuthn\/$/m',
            $include
        );
        self::assertDoesNotMatchRegularExpression('/^vendor_manual\/$/m', $include);
        self::assertSame(
            "v2.2.0\n",
            str_replace("\r\n", "\n", file_get_contents(
                dirname(__DIR__, 2) . '/vendor_manual/WebAuthn/VERSION'
            ))
        );
        self::assertFileExists(
            dirname(__DIR__, 2) . '/vendor_manual/WebAuthn/LICENSE'
        );
    }
}
