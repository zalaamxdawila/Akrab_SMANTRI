<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SingleDomainRoutingTest extends TestCase
{
    public function testProductionAcceptsOnlyCanonicalAkrabHost(): void
    {
        self::assertTrue(applicationHostIsAllowed('akrab.portodq.com', 'production'));
        self::assertTrue(applicationHostIsAllowed('akrab.portodq.com:443', 'production'));
        self::assertFalse(applicationHostIsAllowed('akrabsuperadmin.portodq.com', 'production'));
        self::assertFalse(applicationHostIsAllowed('www.akrab.portodq.com', 'production'));
        self::assertFalse(applicationHostIsAllowed('akrab.portodq.com.attacker.example', 'production'));
    }

    public function testDevelopmentMayUseLoopbackHosts(): void
    {
        self::assertTrue(applicationHostIsAllowed('localhost:8080', 'development'));
        self::assertTrue(applicationHostIsAllowed('127.0.0.1:8080', 'testing'));
    }

    public function testCsrfTokenMayComeFromFormOrJsonRequestHeader(): void
    {
        self::assertSame('form-token', csrfTokenFromRequest(
            ['_csrf' => 'form-token'],
            ['HTTP_X_CSRF_TOKEN' => 'header-token']
        ));
        self::assertSame('header-token', csrfTokenFromRequest(
            [],
            ['HTTP_X_CSRF_TOKEN' => 'header-token']
        ));
        self::assertNull(csrfTokenFromRequest([], []));
    }

    public function testFrontControllersDoNotRouteByLegacySuperadminHostname(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['config.php', 'index.php', 'login.php'] as $file) {
            $contents = file_get_contents($root . '/' . $file);
            self::assertNotFalse($contents);
            self::assertStringNotContainsString('akrabsuperadmin.portodq.com', $contents);
        }

        $index = file_get_contents($root . '/index.php');
        self::assertNotFalse($index);
        self::assertStringContainsString("dashboardForRole((string) \$_SESSION['role'])", $index);
    }

    public function testReleaseContainsSingleDomainAccountAndPasskeySurface(): void
    {
        $include = file_get_contents(dirname(__DIR__, 2) . '/deployment/include.txt');
        self::assertNotFalse($include);

        foreach (['lupa_password.php', 'reset_password.php', 'superadmin_passkey.php', 'vendor_manual/WebAuthn/'] as $path) {
            self::assertMatchesRegularExpression(
                '/^' . preg_quote($path, '/') . '$/m',
                $include,
                "Release allowlist is missing {$path}"
            );
        }
    }

    public function testPasskeyJsonPostsUseCsrfHeaderWithoutInternalErrorDisclosure(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/superadmin_passkey.php');
        self::assertNotFalse($contents);
        self::assertStringContainsString("'X-CSRF-Token': csrfTokenValue", $contents);
        self::assertStringContainsString('parse_url(BASE_URL, PHP_URL_HOST)', $contents);
        self::assertStringNotContainsString("\$_SERVER['HTTP_HOST']", $contents);
        self::assertStringNotContainsString('$e->getFile()', $contents);
        self::assertStringNotContainsString('$e->getLine()', $contents);
    }
}
