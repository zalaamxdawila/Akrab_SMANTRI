<?php

use PHPUnit\Framework\TestCase;

final class SchemaSnapshotTest extends TestCase
{
    public function testSnapshotContainsEveryRuntimeManagedTable(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

        foreach ([
            'users',
            'parent_student_links',
            'audit_log',
            'registration_attempts',
            'csv_import_batches',
            'kuesioner',
            'hasil_deteksi',
            'konsumsi_ttd',
            'konsultasi',
            'balasan_konsultasi',
            'riwayat_haid',
            'artikel_edukasi',
            'schema_migrations',
        ] as $table) {
            self::assertStringContainsString(
                "CREATE TABLE IF NOT EXISTS {$table}",
                $schema
            );
        }
    }

    public function testSnapshotContainsVerifiedParentLinkWorkflow(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

        self::assertStringContainsString("'orangtua'", $schema);
        self::assertStringContainsString("status ENUM('pending', 'approved', 'rejected')", $schema);
        self::assertStringContainsString('requested_student_username VARCHAR(50)', $schema);
        self::assertStringContainsString('reviewed_by INT', $schema);
    }

    public function testSnapshotDoesNotCreateApplicationUsers(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

        self::assertDoesNotMatchRegularExpression('/INSERT\s+INTO\s+users/i', $schema);
    }

    public function testSnapshotStoresModelMetadata(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        self::assertStringContainsString('model_version VARCHAR(80)', $schema);
        self::assertStringContainsString('model_checksum CHAR(64)', $schema);
    }
}
