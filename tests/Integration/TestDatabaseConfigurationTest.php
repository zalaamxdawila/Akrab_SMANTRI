<?php

use PHPUnit\Framework\TestCase;

final class TestDatabaseConfigurationTest extends TestCase
{
    public function testFixtureConfigurationCannotTargetProductionDatabase(): void
    {
        $configuration = parse_ini_file(
            dirname(__DIR__, 2) . '/.env.testing.example',
            false,
            INI_SCANNER_RAW
        );

        self::assertSame('testing', $configuration['AKRAB_APP_ENV']);
        self::assertSame('akrab_test', $configuration['AKRAB_DB_NAME']);
        self::assertNotSame('u602402025_akrab', $configuration['AKRAB_DB_NAME']);
        self::assertSame('false', $configuration['CLINICAL_RISK_ENABLED']);
    }
}
