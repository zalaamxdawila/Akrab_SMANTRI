<?php

use PHPUnit\Framework\TestCase;

final class ClinicalRiskFeatureFlagTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CLINICAL_RISK_ENABLED');
        putenv('CLINICAL_OWNER_APPROVED');
        putenv('CLINICAL_MODEL_APPROVED');
        putenv('CLINICAL_SPEC_VERSION');
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
        putenv('CLINICAL_OWNER_APPROVED=true');
        putenv('CLINICAL_MODEL_APPROVED=true');
        putenv('CLINICAL_SPEC_VERSION=spec-v1');

        self::assertTrue(isClinicalRiskEnabled());
    }

    public function testFeatureFailsClosedWithoutClinicalApproval(): void
    {
        putenv('CLINICAL_RISK_ENABLED=true');
        self::assertFalse(isClinicalRiskEnabled());
    }
}
