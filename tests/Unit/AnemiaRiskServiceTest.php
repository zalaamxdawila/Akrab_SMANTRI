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
        self::assertSame('model-v1', $result['model_version']);
        self::assertSame(64, strlen($result['model_checksum']));
        self::assertGreaterThanOrEqual(0, $result['probability']);
        self::assertLessThanOrEqual(0.99, $result['probability']);
        self::assertEqualsWithDelta(1 / (1 + exp(12.95)), $result['probability'], 0.000001);
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

        self::assertEqualsWithDelta(-12.95, $result['z'], 0.0001);
        self::assertEqualsWithDelta(
            1 / (1 + exp(12.95)),
            $result['probability'],
            0.000001
        );
        self::assertSame('rendah', $result['category']);
        self::assertSame('Simulasi Model Penelitian', $result['status_label']);
        self::assertCount(4, $result['terms']);
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

    public function testQuestionnairePersistsModelMetadata(): void
    {
        $root = dirname(__DIR__, 2);
        $route = file_get_contents($root . '/siswa/kuesioner.php');
        $service = file_get_contents($root . '/app/Services/QuestionnaireService.php');

        self::assertStringContainsString('model_version', $service);
        self::assertStringContainsString('model_checksum', $service);
        self::assertStringContainsString('QuestionnaireService', $route);
    }
}
