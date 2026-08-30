<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningStaffViewTest extends TestCase
{
    public function testStaffViewShowsStageScoresRecommendationsAndDisclaimer(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/views/staged_screening_staff.php');

        self::assertNotFalse($view);
        self::assertStringContainsString('function renderStagedScreeningForStaff', $view);
        self::assertStringContainsString('Rerata gejala', $view);
        self::assertStringContainsString('Skor faktor risiko', $view);
        self::assertStringContainsString('recommendations', $view);
        self::assertStringContainsString('disclaimer', $view);
        self::assertStringContainsString('escape_output(', $view);
    }

    public function testUksAndSuperadminDetailRouteStagedResultsToStaffView(): void
    {
        foreach (['uks/detail_siswa.php', 'superadmin/questionnaire_results.php'] as $path) {
            $source = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
            self::assertStringContainsString("views/staged_screening_staff.php", $source, $path);
            self::assertStringContainsString('StagedScreeningResultPresenter', $source, $path);
            self::assertStringContainsString('renderStagedScreeningForStaff(', $source, $path);
        }
    }

    public function testVerifiedParentDashboardCanRenderTheSameStagedResult(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/orangtua/dashboard.php');

        self::assertStringContainsString('views/staged_screening_staff.php', $source);
        self::assertStringContainsString('StagedScreeningResultPresenter', $source);
        self::assertStringContainsString('renderStagedScreeningForStaff(', $source);
    }

    public function testLegacyHistoryChartsAreHiddenForLatestStagedResults(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['orangtua/dashboard.php', 'uks/detail_siswa.php'] as $path) {
            $source = file_get_contents($root . '/' . $path);
            self::assertIsString($source);
            self::assertStringContainsString(
                '$questionnaireHistory && !$isStagedScreening',
                $source,
                $path
            );
            self::assertStringContainsString("empty(\$row['versi_screening'])", $source, $path);
        }
    }
}
