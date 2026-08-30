<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningRepositoryContractTest extends TestCase
{
    public function testRepositoryPersistsStagesWithOwnershipAndServerSideGate(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Repositories/PdoStagedScreeningStore.php'
        );

        self::assertNotFalse($source);
        self::assertStringContainsString('implements StagedScreeningStore', $source);
        self::assertStringContainsString('beginTransaction()', $source);
        self::assertStringContainsString('QuestionnaireEligibility', $source);
        foreach ([
            'jenis_kelamin',
            'tahap_screening',
            'rerata_gejala',
            'persentase_faktor_risiko',
            'hasil_screening',
            'versi_screening',
        ] as $column) {
            self::assertStringContainsString($column, $source);
        }
        self::assertMatchesRegularExpression('/WHERE id = \?\s+AND user_id = \?/s', $source);
        self::assertStringContainsString("tahap_screening = 'faktor_risiko_tersedia'", $source);
        self::assertStringContainsString('rerata_gejala > ?', $source);
        self::assertStringNotContainsString('AnemiaRiskService', $source);
        self::assertStringNotContainsString('INSERT INTO hasil_deteksi', $source);
    }
}
