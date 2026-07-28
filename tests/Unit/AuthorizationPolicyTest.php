<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthorizationPolicyTest extends TestCase
{
    public function testOnlyKnownRolesAreAccepted(): void
    {
        self::assertTrue(isApplicationRole('siswa'));
        self::assertTrue(isApplicationRole('orangtua'));
        self::assertTrue(isApplicationRole('uks'));
        self::assertFalse(isApplicationRole('admin'));
        self::assertFalse(isApplicationRole(''));
    }

    public function testEveryRoleHasAnExplicitDashboard(): void
    {
        self::assertSame('siswa/dashboard.php', dashboardForRole('siswa'));
        self::assertSame('orangtua/dashboard.php', dashboardForRole('orangtua'));
        self::assertSame('uks/dashboard.php', dashboardForRole('uks'));
    }

    public function testUnknownRoleHasNoFallbackDashboard(): void
    {
        $this->expectException(InvalidArgumentException::class);
        dashboardForRole('unexpected');
    }

    public function testRoleActionMatrixDefaultsToDeny(): void
    {
        self::assertTrue(roleCan('siswa', 'manage_own_health'));
        self::assertTrue(roleCan('orangtua', 'view_linked_child'));
        self::assertTrue(roleCan('uks', 'manage_school_health'));
        self::assertFalse(roleCan('siswa', 'manage_school_health'));
        self::assertFalse(roleCan('uks', 'unknown_action'));
        self::assertFalse(roleCan('unknown', 'manage_own_health'));
    }
}
