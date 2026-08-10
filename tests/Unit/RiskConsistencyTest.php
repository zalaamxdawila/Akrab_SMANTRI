<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RiskConsistencyTest extends TestCase
{
    public function testCanonicalRiskAndAdviceMappingIsExplicit(): void
    {
        self::assertSame('tinggi', canonicalRiskCategory('tinggi'));
        self::assertSame('rendah', canonicalRiskCategory('unknown'));
        self::assertSame('berat', adviceCategoryForRisk('tinggi'));
        self::assertSame('sedang', adviceCategoryForRisk('sedang'));
        self::assertSame('tidak_anemia', adviceCategoryForRisk('rendah'));
    }

    public function testLatestQueriesUseDateAndIdTieBreakers(): void
    {
        $export = file_get_contents(dirname(__DIR__, 2) . '/uks/export_csv.php');
        self::assertStringContainsString('ORDER BY tanggal DESC, id DESC', $export);
        $dashboard = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');
        self::assertStringContainsString('latestDetectionForStudent(', $dashboard);
        $repository = file_get_contents(
            dirname(__DIR__, 2)
                . '/app/Repositories/QuestionnaireAnalyticsRepository.php'
        );
        self::assertStringContainsString(
            'ORDER BY created_at DESC, tanggal DESC, id DESC',
            $repository
        );
    }

    public function testAdviceLookupUsesRiskMapping(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/hasil_deteksi.php');
        self::assertStringContainsString('adviceCategoryForRisk', $contents);
        self::assertStringContainsString('canonicalRiskCategory', $contents);
    }
}
