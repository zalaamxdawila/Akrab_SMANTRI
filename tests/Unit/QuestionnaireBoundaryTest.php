<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireBoundaryTest extends TestCase
{
    public function testQuestionnairePageDelegatesPersistenceToService(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('new QuestionnaireService($pdo)', $contents);
        self::assertStringNotContainsString('INSERT INTO kuesioner', $contents);
        self::assertStringNotContainsString('beginTransaction()', $contents);
    }

    public function testServiceOwnsRiskEvaluationAndAtomicWrites(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/app/Services/QuestionnaireService.php');

        self::assertStringContainsString('validateQuestionnaireInput', $contents);
        self::assertStringContainsString('beginTransaction()', $contents);
        self::assertStringContainsString('rollBack()', $contents);
        self::assertStringContainsString('model_checksum', $contents);
    }
}
