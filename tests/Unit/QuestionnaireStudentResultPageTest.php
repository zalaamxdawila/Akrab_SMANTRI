<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireStudentResultPageTest extends TestCase
{
    public function testStudentResultUsesOwnedStagedRecordAndSafePresentation(): void
    {
        $route = file_get_contents(
            dirname(__DIR__, 2) . '/siswa/hasil_deteksi.php'
        );

        self::assertNotFalse($route);
        self::assertStringContainsString("check_role('siswa')", $route);
        self::assertStringContainsString('new PdoStagedScreeningStore($pdo)', $route);
        self::assertStringContainsString('resultForStudent(', $route);
        self::assertStringContainsString("\$_GET['questionnaire_id']", $route);
        self::assertStringContainsString('new StagedScreeningResultPresenter()', $route);
        self::assertStringContainsString("tahap_screening'] === 'faktor_risiko_tersedia'", $route);
        self::assertStringContainsString('escape_output(', $route);
        self::assertStringContainsString('QuestionnaireAnalyticsRepository', $route);
        self::assertStringContainsString('latestPrimaryForStudent($userId)', $route);
        self::assertStringContainsString('latestDetectionForStudent(', $route);
        self::assertStringNotContainsString('data_laboratorium.php', $route);
    }
}
