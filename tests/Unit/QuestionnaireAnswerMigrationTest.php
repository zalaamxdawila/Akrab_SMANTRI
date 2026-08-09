<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireAnswerMigrationTest extends TestCase
{
    public function testSchemaAndAdditiveMigrationDefineNullableSnapshot(): void
    {
        $root = dirname(__DIR__, 2);
        $schema = file_get_contents($root . '/database/schema.sql');
        $migration = file_get_contents(
            $root . '/database/migrations/015_questionnaire_answer_snapshot.php'
        );

        self::assertNotFalse($schema);
        self::assertNotFalse($migration);
        self::assertStringContainsString('answers_snapshot JSON NULL', $schema);
        self::assertStringContainsString("'version' => '015_questionnaire_answer_snapshot'", $migration);
        self::assertStringContainsString("SHOW COLUMNS FROM kuesioner LIKE", $migration);
        self::assertStringContainsString('ADD COLUMN answers_snapshot JSON NULL', $migration);
    }
}
