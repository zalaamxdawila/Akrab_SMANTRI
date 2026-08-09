<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MigrationMariaDbCompatibilityTest extends TestCase
{
    public function testMetadataQueriesDoNotBindPlaceholdersInShowStatements(): void
    {
        $root = dirname(__DIR__, 2);
        $migration013 = file_get_contents(
            $root . '/database/migrations/013_questionnaire_auth_support.php'
        );
        $migration014 = file_get_contents(
            $root . '/database/migrations/014_hash_password_reset_tokens.php'
        );

        self::assertNotFalse($migration013);
        self::assertNotFalse($migration014);
        self::assertStringNotContainsString('LIKE ?', $migration013);
        self::assertStringNotContainsString('Key_name = ?', $migration013);
        self::assertStringNotContainsString('LIKE ?', $migration014);
        self::assertStringNotContainsString('Key_name = ?', $migration014);
        self::assertStringContainsString('$pdo->quote($column)', $migration013);
        self::assertStringContainsString('$pdo->quote($column)', $migration014);
    }
}
