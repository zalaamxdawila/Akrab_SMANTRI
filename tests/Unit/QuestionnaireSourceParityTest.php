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
}
