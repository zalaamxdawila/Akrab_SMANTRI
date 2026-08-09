<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireStudentResultPageTest extends TestCase
{
    public function testStudentResultUsesOwnedActiveRecordsAndSharedPresentation(): void
    {
        $route = file_get_contents(
            dirname(__DIR__, 2) . '/siswa/hasil_deteksi.php'
        );

        self::assertNotFalse($route);
        self::assertStringContainsString("check_role('siswa')", $route);
        self::assertStringContainsString('historyForStudent((int) $user_id)', $route);
        self::assertStringContainsString('latestDetectionForStudent((int) $user_id)', $route);
        self::assertStringContainsString('new QuestionnaireResultPresenter()', $route);
        self::assertStringContainsString('renderQuestionnaireResult(', $route);
        self::assertStringContainsString("require_once '../views/questionnaire_analytics.php'", $route);
    }
}
