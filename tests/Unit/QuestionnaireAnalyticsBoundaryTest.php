<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireAnalyticsBoundaryTest extends TestCase
{
    public function testAnalyticsPagesEnforceRoleBoundaries(): void
    {
        $root = dirname(__DIR__, 2);
        $uks = file_get_contents($root . '/uks/hasil_kuesioner.php');
        $superadmin = file_get_contents(
            $root . '/superadmin/questionnaire_results.php'
        );
        $parent = file_get_contents($root . '/orangtua/dashboard.php');

        self::assertStringContainsString("check_role('uks')", $uks);
        self::assertStringContainsString('SuperadminGuard::authorize', $superadmin);
        self::assertStringContainsString('approvedStudentForParent', $parent);
        self::assertStringNotContainsString('$_GET', $parent);
    }

    public function testEveryAudienceReceivesChartAndExplanationUi(): void
    {
        $root = dirname(__DIR__, 2);
        $sharedView = file_get_contents(
            $root . '/views/questionnaire_analytics.php'
        );
        self::assertStringContainsString('new Chart(', $sharedView);
        self::assertStringContainsString(
            'renderQuestionnaireInsights',
            $sharedView
        );
        self::assertStringContainsString(
            "getAttribute('data-bs-theme')",
            $sharedView
        );

        foreach ([
            '/uks/hasil_kuesioner.php',
            '/uks/detail_siswa.php',
            '/superadmin/questionnaire_results.php',
            '/orangtua/dashboard.php',
        ] as $relativePath) {
            $contents = file_get_contents($root . $relativePath);
            self::assertStringContainsString(
                'renderQuestionnaire',
                $contents,
                $relativePath
            );
            self::assertStringContainsString(
                'QuestionnaireInsights',
                $contents,
                $relativePath
            );
        }
        self::assertStringContainsString('disclaimer', $sharedView);
    }

    public function testSingleResponseChartConnectsTheFourScoreAspects(): void
    {
        require_once dirname(__DIR__, 2) . '/views/questionnaire_analytics.php';

        ob_start();
        renderQuestionnaireHistoryChartScript('studentChart', [
            'labels' => ['01 Agu 2026'],
            'series' => [
                'gejala' => [25.0],
                'makan' => [50.0],
                'pengetahuan' => [75.0],
                'sikap' => [100.0],
            ],
        ]);
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            'labels: ["Keluhan \\u0026 gejala","Pola makan","Pengetahuan","Sikap \\u0026 kesadaran"]',
            $output
        );
        self::assertStringContainsString(
            'data: [25,50,75,100]',
            $output
        );
        self::assertStringContainsString('showLine: true', $output);
        self::assertStringContainsString('borderWidth: 3', $output);
    }

    public function testParentUksAndSuperadminUseCompleteResultPresenter(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            '/orangtua/dashboard.php',
            '/uks/detail_siswa.php',
            '/superadmin/questionnaire_results.php',
        ] as $relativePath) {
            $contents = file_get_contents($root . $relativePath);
            self::assertNotFalse($contents);
            self::assertStringContainsString(
                'QuestionnaireResultPresenter',
                $contents,
                $relativePath
            );
            self::assertStringContainsString(
                'renderQuestionnaireResult(',
                $contents,
                $relativePath
            );
        }
    }
}
