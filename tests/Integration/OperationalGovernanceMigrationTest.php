<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OperationalGovernanceMigrationTest extends TestCase
{
    public function testAllOperationalTablesReceiveAdditiveGovernanceMetadata(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/database/migrations/012_operational_governance.php'
        );
        foreach ([
            'konsultasi',
            'balasan_konsultasi',
            'artikel_edukasi',
            'saran_edukasi',
            'jadwal_notifikasi',
            'log_notifikasi',
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
        self::assertStringContainsString('information_schema.TABLE_CONSTRAINTS', $source);
        self::assertDoesNotMatchRegularExpression('/\b(?:DROP|DELETE\s+FROM)\b/i', $source);
    }
}
