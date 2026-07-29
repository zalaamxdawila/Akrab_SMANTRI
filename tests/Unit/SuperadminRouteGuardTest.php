<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SuperadminRouteGuardTest extends TestCase
{
    public function testAllReadOnlyRoutesUseDatabaseBackedGuard(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'superadmin/dashboard.php',
            'superadmin/users.php',
            'superadmin/user_detail.php',
            'superadmin/audit.php',
        ] as $path) {
            self::assertFileExists($root . '/' . $path);
            $contents = file_get_contents($root . '/' . $path);
            self::assertStringContainsString('SuperadminGuard', $contents);
            self::assertStringNotContainsString(
                "\$_SERVER['REQUEST_METHOD'] === 'POST'",
                $contents
            );
            self::assertStringNotContainsString('password_hash', $contents);
        }
    }

    public function testLayoutHasAccessibleNavigationAndMainLandmark(): void
    {
        $contents = file_get_contents(
            dirname(__DIR__, 2) . '/views/superadmin/layout.php'
        );

        self::assertStringContainsString('class="skip-link"', $contents);
        self::assertStringContainsString('<main id="main-content"', $contents);
        self::assertStringContainsString('aria-current="page"', $contents);
        self::assertStringContainsString('superadmin.css', $contents);
    }

    public function testGuardRejectsImpersonationAndMissingPermission(): void
    {
        require_once dirname(__DIR__, 2)
            . '/app/Security/SuperadminGuard.php';

        $normal = new ActorContext(1, 1, 'superadmin', 'superadmin');
        self::assertTrue(SuperadminGuard::contextIsAuthorized($normal));

        $impersonated = new ActorContext(
            1,
            2,
            'superadmin',
            'siswa',
            10
        );
        self::assertFalse(
            SuperadminGuard::contextIsAuthorized($impersonated)
        );
    }
}
