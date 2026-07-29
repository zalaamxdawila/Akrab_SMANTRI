<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminHealthService.php';

final class SuperadminQuestionnaireGovernanceTest extends TestCase
{
    public function testCorrectionUsesDomainRangesAndRedactsAuditValues(): void
    {
        $pdo = Sprint29Fixture::database();
        (new SuperadminHealthService($pdo))->correctQuestionnaire(
            Sprint29Fixture::actor(),
            1,
            ['kadar_hb' => '12.1', 'skor_gejala' => '21'],
            'correction',
            'q-1'
        );
        self::assertSame(12.1, (float) $pdo->query(
            'SELECT kadar_hb FROM kuesioner WHERE id = 1'
        )->fetchColumn());
        $metadata = $pdo->query(
            'SELECT metadata_json FROM audit_log ORDER BY id DESC LIMIT 1'
        )->fetchColumn();
        self::assertStringContainsString('changed_fields', $metadata);
        self::assertStringNotContainsString('12.1', $metadata);
    }

    public function testClinicalResultCorrectionNeverCallsModel(): void
    {
        $pdo = Sprint29Fixture::database();
        (new SuperadminHealthService($pdo))->correctRiskResult(
            Sprint29Fixture::actor(),
            1,
            ['probabilitas_risiko' => '0.45', 'kategori_risiko' => 'sedang',
                'tanggal' => '2026-07-21'],
            'verification',
            'r-1'
        );
        self::assertSame('sedang', $pdo->query(
            'SELECT kategori_risiko FROM hasil_deteksi WHERE id = 1'
        )->fetchColumn());
    }

    public function testRiskCategoryMustMatchProbability(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SuperadminHealthService(Sprint29Fixture::database()))
            ->correctRiskResult(
                Sprint29Fixture::actor(),
                1,
                ['probabilitas_risiko' => '0.9', 'kategori_risiko' => 'rendah',
                    'tanggal' => '2026-07-21'],
                'verification',
                'mismatch'
            );
    }
}
