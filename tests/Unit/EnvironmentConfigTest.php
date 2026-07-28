<?php

use PHPUnit\Framework\TestCase;

final class EnvironmentConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('AKRAB_TEST_REQUIRED');
        putenv('AKRAB_TEST_OPTIONAL');
    }

    public function testMissingRequiredValueFailsWithGenericMessage(): void
    {
        putenv('AKRAB_TEST_REQUIRED');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required application configuration is missing.');

        requireEnvironmentValue('AKRAB_TEST_REQUIRED');
    }

    public function testRequiredValueComesFromEnvironment(): void
    {
        putenv('AKRAB_TEST_REQUIRED=configured-value');

        self::assertSame(
            'configured-value',
            requireEnvironmentValue('AKRAB_TEST_REQUIRED')
        );
    }

    public function testOptionalValueUsesExplicitFallback(): void
    {
        putenv('AKRAB_TEST_OPTIONAL');

        self::assertSame(
            'fallback-value',
            environmentValue('AKRAB_TEST_OPTIONAL', 'fallback-value')
        );
    }
}
