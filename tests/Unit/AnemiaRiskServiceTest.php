<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AnemiaRiskServiceTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('CLINICAL_RISK_ENABLED=true');
        putenv('CLINICAL_OWNER_APPROVED=true');
        putenv('CLINICAL_MODEL_APPROVED=true');
        putenv('CLINICAL_SPEC_VERSION=spec-v1');
        putenv('CLINICAL_MODEL_VERSION=model-v1');
        putenv('CLINICAL_MODEL_CHECKSUM=' . str_repeat('a', 64));
    }

    protected function tearDown(): void
    {
        foreach (['CLINICAL_RISK_ENABLED', 'CLINICAL_OWNER_APPROVED', 'CLINICAL_MODEL_APPROVED', 'CLINICAL_SPEC_VERSION', 'CLINICAL_MODEL_VERSION', 'CLINICAL_MODEL_CHECKSUM'] as $name) {
            putenv($name);
        }
    }

    public function testResearchSimulationCanRunWithoutClinicalApproval(): void
    {
        putenv('CLINICAL_RISK_ENABLED=false');
        putenv('CLINICAL_OWNER_APPROVED=false');
        putenv('CLINICAL_MODEL_APPROVED=false');
        putenv('AKRAB_RESEARCH_MODEL_ENABLED=true');
        try {
            $result = (new AnemiaRiskService())->evaluate([
                'kadar_hb'=>12, 'kadar_mchc'=>33, 'kadar_mcv'=>85, 'kadar_mch'=>29,
                'skor_gejala'=>10, 'skor_makan'=>12, 'mens_teratur'=>'ya',
            ]);
            self::assertSame('rendah', $result['category']);
        } finally {
            putenv('AKRAB_RESEARCH_MODEL_ENABLED');
        }
    }

    public function testEvaluationReturnsVersionedBoundedLogisticResult(): void
    {
        $result = (new AnemiaRiskService())->evaluate([
            'kadar_hb' => 12,
            'kadar_mchc' => 33,
            'kadar_mcv' => 85,
            'kadar_mch' => 29,
            'skor_gejala' => 10,
            'skor_makan' => 12,
            'mens_teratur' => 'ya',
        ]);
        self::assertArrayHasKey('probability', $result);
        self::assertArrayHasKey('category', $result);
        self::assertSame('AKRAB-RESEARCH-CENTERED-v1.1', $result['model_version']);
        self::assertSame(64, strlen($result['model_checksum']));
        self::assertGreaterThanOrEqual(0, $result['probability']);
        self::assertLessThanOrEqual(0.99, $result['probability']);
        self::assertEqualsWithDelta(1 / (1 + exp(2.18)), $result['probability'], 0.000001);
        self::assertSame('rendah', $result['category']);
    }

    public function testEvaluationRejectsIncompleteLabInsteadOfUsingHeuristicRisk(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AnemiaRiskService())->evaluate([
            'kadar_hb' => null, 'skor_gejala' => 10,
            'skor_makan' => 12, 'mens_teratur' => 'ya',
        ]);
    }

    public function testExtremeInputIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AnemiaRiskService())->evaluate([
            'kadar_hb' => 999,
            'skor_gejala' => 10,
            'skor_makan' => 12,
            'mens_teratur' => 'ya',
        ]);
    }

    public function testLogisticExplanationExposesTransparentResearchCalculation(): void
    {
        $result = (new AnemiaRiskService())->explainLogistic([
            'kadar_hb' => 12,
            'kadar_mchc' => 33,
            'kadar_mcv' => 85,
            'kadar_mch' => 29,
        ]);

        self::assertEqualsWithDelta(-2.18, $result['z'], 0.0001);
        self::assertEqualsWithDelta(
            1 / (1 + exp(2.18)),
            $result['probability'],
            0.000001
        );
        self::assertSame('rendah', $result['category']);
        self::assertSame('Simulasi Model Penelitian', $result['status_label']);
        self::assertCount(4, $result['terms']);
        self::assertSame(29.5, $result['terms'][1]['reference_value']);
        self::assertEqualsWithDelta(-0.5, $result['terms'][1]['centered_value'], 0.0001);
    }

    public function testCenteredFormulaProducesUsefulSpreadAcrossPlausibleProfiles(): void
    {
        $service = new AnemiaRiskService();

        $normal = $service->explainLogistic([
            'kadar_hb' => 13, 'kadar_mch' => 30,
            'kadar_mchc' => 34, 'kadar_mcv' => 92,
        ]);
        $borderline = $service->explainLogistic([
            'kadar_hb' => 11, 'kadar_mch' => 27,
            'kadar_mchc' => 31, 'kadar_mcv' => 80,
        ]);
        $low = $service->explainLogistic([
            'kadar_hb' => 9, 'kadar_mch' => 23,
            'kadar_mchc' => 29, 'kadar_mcv' => 70,
        ]);

        self::assertLessThan(0.10, $normal['probability']);
        self::assertGreaterThan(0.33, $borderline['probability']);
        self::assertLessThan(0.66, $borderline['probability']);
        self::assertGreaterThan(0.90, $low['probability']);
    }

    public function testLogisticExplanationRequiresAllFourLaboratoryValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AnemiaRiskService())->explainLogistic([
            'kadar_hb' => 12,
            'kadar_mchc' => 33,
            'kadar_mcv' => 85,
        ]);
    }

    public function testServiceFailsClosedWithoutApproval(): void
    {
        putenv('CLINICAL_OWNER_APPROVED=false');
        $this->expectException(RuntimeException::class);
        (new AnemiaRiskService())->evaluate(['skor_gejala' => 1, 'skor_makan' => 1, 'mens_teratur' => 'ya']);
    }

    public function testLegacyLabServiceKeepsModelMetadataSeparateFromStagedFlow(): void
    {
        $root = dirname(__DIR__, 2);
        $route = file_get_contents($root . '/siswa/kuesioner.php');
        $service = file_get_contents($root . '/app/Services/QuestionnaireService.php');

        self::assertStringContainsString('model_version', $service);
        self::assertStringContainsString('model_checksum', $service);
        self::assertStringContainsString('StagedScreeningService', $route);
        self::assertStringNotContainsString('AnemiaRiskService', $route);
    }

    public function testCenteredResultBackfillIsVersionedAndIdempotent(): void
    {
        $migration = file_get_contents(
            dirname(__DIR__, 2) . '/database/migrations/018_recalculate_centered_logistic_results.php'
        );

        self::assertStringContainsString('AKRAB-RESEARCH-CENTERED-v1.1', $migration);
        self::assertStringContainsString('(k.kadar_mch - 29.5)', $migration);
        self::assertStringContainsString('(k.kadar_mchc - 33.2)', $migration);
        self::assertStringContainsString('(k.kadar_mcv - 90.0)', $migration);
        self::assertStringContainsString('NOT EXISTS', $migration);
    }
}
