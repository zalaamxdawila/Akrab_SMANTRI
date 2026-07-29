<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParentLinkArchiveMigrationTest extends TestCase
{
    public function testMigrationIsAdditiveIdempotentAndHasArchiveIndex(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/database/migrations/010_parent_link_archive.php'
        );
        self::assertStringContainsString("SHOW COLUMNS FROM", $source);
        self::assertStringContainsString("'archived_at'", $source);
        self::assertStringContainsString("'archived_by'", $source);
        self::assertStringContainsString("'archive_reason'", $source);
        self::assertStringContainsString('idx_parent_links_archive_status', $source);
        self::assertStringContainsString('fk_parent_links_archived_by', $source);
        self::assertStringNotContainsString('DROP ', strtoupper($source));
        self::assertDoesNotMatchRegularExpression(
            '/\bDELETE\s+FROM\b/i',
            $source
        );
    }
}
