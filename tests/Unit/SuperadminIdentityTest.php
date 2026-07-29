<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SuperadminIdentityTest extends TestCase
{
    public function testSchemaDefinesOneSuperadminAndAccountStatus(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

        self::assertStringContainsString("'superadmin'", $schema);
        self::assertStringContainsString("ENUM('active', 'inactive', 'archived')", $schema);
        self::assertStringContainsString('superadmin_key', $schema);
        self::assertStringContainsString('uq_users_single_superadmin', $schema);
    }

    public function testMigrationIsAdditiveAndEnforcesSingleton(): void
    {
        $path = dirname(__DIR__, 2) . '/database/migrations/008_superadmin_identity.php';

        self::assertFileExists($path);
        $migration = file_get_contents($path);
        self::assertStringContainsString('008_superadmin_identity', $migration);
        self::assertStringContainsString('superadmin_key', $migration);
        self::assertStringContainsString('uq_users_single_superadmin', $migration);
        self::assertStringNotContainsString('DROP TABLE', strtoupper($migration));
    }

    public function testProvisioningIsCliOnlyAndDoesNotAcceptRoleInput(): void
    {
        $contents = file_get_contents(
            dirname(__DIR__, 2) . '/tools/provision_superadmin.php'
        );

        self::assertStringContainsString("PHP_SAPI !== 'cli'", $contents);
        self::assertStringContainsString('AKRAB_PROVISION_SUPERADMIN_PASSWORD', $contents);
        self::assertStringContainsString(
            "hash_equals('replace_before_running_cli_tool', \$password)",
            $contents
        );
        self::assertStringNotContainsString("'role:'", $contents);
        self::assertStringNotContainsString('echo $password', $contents);
    }

    public function testProductionReleaseIncludesSuperadminProvisioningTool(): void
    {
        $include = file_get_contents(
            dirname(__DIR__, 2) . '/deployment/include.txt'
        );

        self::assertStringContainsString(
            'tools/provision_superadmin.php',
            $include
        );
    }
}
