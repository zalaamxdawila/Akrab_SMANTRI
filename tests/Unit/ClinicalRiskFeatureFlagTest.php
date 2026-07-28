<?php

use PHPUnit\Framework\TestCase;

final class ClinicalRiskFeatureFlagTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CLINICAL_RISK_ENABLED');
    }

    public function testClinicalRiskDefaultsToDisabled(): void
    {
        putenv('CLINICAL_RISK_ENABLED');

        self::assertFalse(isClinicalRiskEnabled());
    }

    public function testUnknownValueFailsClosed(): void
    {
        putenv('CLINICAL_RISK_ENABLED=unexpected');

        self::assertFalse(isClinicalRiskEnabled());
    }

    public function testExplicitTrueEnablesClinicalRisk(): void
    {
        putenv('CLINICAL_RISK_ENABLED=true');

        self::assertTrue(isClinicalRiskEnabled());
    }
}
