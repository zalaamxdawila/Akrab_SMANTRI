<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ClinicalGovernanceTest extends TestCase
{
    public function testClinicalClaimsAreExplicitlyScreeningOnly(): void
    {
        self::assertStringContainsString('skrining risiko', strtolower(AKRAB_CLINICAL_DISCLAIMER));
        self::assertStringContainsString('bukan diagnosis', strtolower(AKRAB_CLINICAL_DISCLAIMER));
        self::assertStringContainsString('darurat', strtolower(AKRAB_EMERGENCY_GUIDANCE));
    }

    public function testGovernanceDocumentsContainRequiredApprovalFields(): void
    {
        $spec = file_get_contents(dirname(__DIR__, 2) . '/docs/clinical-specification.md');
        $card = file_get_contents(dirname(__DIR__, 2) . '/docs/model-card.md');
        foreach (['clinical owner', 'populasi', 'tujuan screening', 'threshold', 'kontraindikasi', 'jalur rujukan'] as $term) {
            self::assertStringContainsString($term, strtolower($spec));
        }
        self::assertStringContainsString('approval record', strtolower($card));
        self::assertStringContainsString('feature flag', strtolower($card));
    }

    public function testClinicalPagesUseTheSafetyGateAndDisclaimer(): void
    {
        $questionnaire = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');
        self::assertStringContainsString('isClinicalRiskEnabled()', $questionnaire);
        self::assertStringContainsString('bukan diagnosis', strtolower($questionnaire));
    }
}
