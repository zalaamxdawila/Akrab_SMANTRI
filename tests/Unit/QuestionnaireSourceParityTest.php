<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireSourceParityTest extends TestCase
{
    public function testVisibleQuestionnaireMatchesCanonicalStagedSections(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        foreach ([
            'Sahabat merasakan cepat lelah bila beraktivitas',
            'Apakah sahabat sudah mengalami menstruasi',
            'Isilah tabel berikut ini sesuai makanan yang sahabat makan setiap hari.',
            'Apakah sahabat ada makan lagi atau snek menjelang tidur ?',
            'Tidak pernah',
        ] as $canonicalText) {
            self::assertStringContainsString($canonicalText, $route);
        }

        foreach (['Hasil Lab Darah', 'Tidak Setuju', 'Pengetahuan Dasar'] as $removedSection) {
            self::assertStringNotContainsString($removedSection, $route);
        }
    }

    public function testCompleteResultIncludesEveryScoredDocumentDiagram(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2) . '/views/questionnaire_analytics.php'
        );

        foreach ([
            'answerSymptomChart',
            'answerAttitudeChart',
            'answerKnowledgeChart',
            'answerDietChart',
        ] as $canvasId) {
            self::assertStringContainsString($canvasId, $view);
        }
    }

    public function testSuperadminQuestionnaireResultsSeparateLegacyAndStagedViewsByMenu(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/superadmin/questionnaire_results.php');

        self::assertStringContainsString("view'] ?? 'baru'", $route);
        self::assertStringContainsString('Hasil Skrining Baru', $route);
        self::assertStringContainsString('Hasil Kuesioner Lama', $route);
        self::assertStringContainsString('questionnaire-results-menu', $route);
        self::assertStringContainsString('$activeQuestionnaireView === \'baru\'', $route);
        self::assertStringContainsString('$activeQuestionnaireView === \'lama\'', $route);
        self::assertStringNotContainsString('id="questionnaire-results-menu" hidden', $route);
    }

    public function testUksQuestionnaireResultsSeparateLegacyAndStagedViewsByMenu(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/uks/hasil_kuesioner.php');

        self::assertStringContainsString("view'] ?? 'ringkasan'", $route);
        self::assertStringContainsString('questionnaire-results-menu', $route);
        self::assertStringContainsString('$activeQuestionnaireView === \'ringkasan\'', $route);
        self::assertStringContainsString('$activeQuestionnaireView === \'baru\'', $route);
        self::assertStringContainsString('$activeQuestionnaireView === \'lama\'', $route);
        self::assertStringContainsString('Hasil Skrining Baru', $route);
        self::assertStringContainsString('Hasil Kuesioner Lama', $route);
    }
}
