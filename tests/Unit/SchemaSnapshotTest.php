<?php

use PHPUnit\Framework\TestCase;

final class SchemaSnapshotTest extends TestCase
{
    public function testSnapshotContainsEveryRuntimeManagedTable(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

        foreach ([
            'users',
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

    public function testSnapshotContainsParentRoleAndLink(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

        self::assertStringContainsString("'orangtua'", $schema);
        self::assertStringContainsString('anak_username VARCHAR(50)', $schema);
    }

    public function testSnapshotDoesNotCreateApplicationUsers(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

        self::assertDoesNotMatchRegularExpression('/INSERT\s+INTO\s+users/i', $schema);
    }
}
