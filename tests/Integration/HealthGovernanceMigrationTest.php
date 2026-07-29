<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HealthGovernanceMigrationTest extends TestCase
{
    public function testMigrationAddsReversibleMetadataToEveryHealthTable(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/database/migrations/011_health_record_governance.php'
        );
        foreach ([
            'kuesioner',
            'hasil_deteksi',
            'kadar_hb',
            'konsumsi_ttd',
            'riwayat_haid',
        ] as $table) {
            self::assertStringContainsString("'{$table}'", $source);
        }
        foreach ([
            'corrected_at',
            'corrected_by',
            'correction_reason',
            'archived_at',
            'archived_by',
            'archive_reason',
        ] as $column) {
            self::assertStringContainsString("'{$column}'", $source);
        }
        self::assertStringContainsString('SHOW COLUMNS FROM', $source);
        self::assertDoesNotMatchRegularExpression('/\b(?:DROP|DELETE\s+FROM)\b/i', $source);
    }
}
