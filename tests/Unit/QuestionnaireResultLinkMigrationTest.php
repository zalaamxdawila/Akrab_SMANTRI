<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireResultLinkMigrationTest extends TestCase
{
    public function testMigrationAddsNullableIndexedQuestionnaireLinkAndRollback(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = file_get_contents(
            $root . '/database/migrations/016_questionnaire_result_link.php'
        );
        $schema = file_get_contents($root . '/database/schema.sql');

        self::assertNotFalse($migration);
        self::assertStringContainsString(
            "'version' => '016_questionnaire_result_link'",
            $migration
        );
        self::assertStringContainsString('questionnaire_id INT NULL', $migration);
        self::assertStringContainsString('idx_detection_questionnaire', $migration);
        self::assertStringContainsString("'down' =>", $migration);
        self::assertMatchesRegularExpression(
            '/CREATE TABLE IF NOT EXISTS hasil_deteksi\s*\([^;]*questionnaire_id INT NULL[^;]*idx_detection_questionnaire/s',
            $schema
        );
        self::assertDoesNotMatchRegularExpression(
            '/CREATE TABLE IF NOT EXISTS kuesioner\s*\([^;]*questionnaire_id INT NULL/s',
            $schema
        );
    }
}
