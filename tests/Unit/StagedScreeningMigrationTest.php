<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningMigrationTest extends TestCase
{
    public function testMigrationAndSchemaAddNullableStagedScreeningFields(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = file_get_contents($root . '/database/migrations/021_staged_screening.php');
        $schema = file_get_contents($root . '/database/schema.sql');

        self::assertNotFalse($migration);
        self::assertStringContainsString("'version' => '021_staged_screening'", $migration);
        foreach ([
            'jenis_kelamin',
            'tahap_screening',
            'rerata_gejala',
            'persentase_faktor_risiko',
            'hasil_screening',
            'versi_screening',
        ] as $column) {
            self::assertStringContainsString($column, $migration);
            self::assertStringContainsString($column, $schema);
        }

        self::assertStringContainsString('idx_kuesioner_screening_stage', $migration);
        self::assertStringContainsString('idx_kuesioner_screening_stage', $schema);
    }
}
