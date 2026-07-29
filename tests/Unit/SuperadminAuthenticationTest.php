<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SuperadminAuthenticationTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED');
    }

    public function testSuperadminFeatureDefaultsToDisabled(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED');

        self::assertFalse(superadminFeatureEnabled());
    }

    public function testSuperadminRequiresFeatureFlagAndActiveStatus(): void
    {
        $user = ['role' => 'superadmin', 'status' => 'active'];

        putenv('AKRAB_SUPERADMIN_ENABLED=false');
        self::assertFalse(userCanAuthenticate($user));

        putenv('AKRAB_SUPERADMIN_ENABLED=true');
        self::assertTrue(userCanAuthenticate($user));

        $user['status'] = 'inactive';
        self::assertFalse(userCanAuthenticate($user));
    }

    public function testExistingRolesStillRequireActiveStatus(): void
    {
        self::assertTrue(userCanAuthenticate(['role' => 'siswa', 'status' => 'active']));
        self::assertFalse(userCanAuthenticate(['role' => 'siswa', 'status' => 'archived']));
        self::assertFalse(userCanAuthenticate(['role' => 'unexpected', 'status' => 'active']));
    }

    public function testLoginLoadsStatusAndUsesCentralAuthenticationPolicy(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/login.php');

        self::assertStringContainsString('status', $contents);
        self::assertStringContainsString('userCanAuthenticate($user)', $contents);
    }
}
