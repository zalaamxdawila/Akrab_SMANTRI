<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningDashboardContractTest extends TestCase
{
    public function testDashboardVerifiesCsrfBeforeAnyPostMutation(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');
        self::assertIsString($source);
        $csrf = strpos($source, 'verifyCsrfOrFail(csrfTokenFromRequest($_POST, $_SERVER))');
        self::assertNotFalse($csrf);
        self::assertLessThan(strpos($source, 'isset($_POST[\'toggle_haid\'])'), $csrf);
        self::assertLessThan(strpos($source, 'isset($_POST[\'confirm_ttd\'])'), $csrf);
    }

    public function testDashboardPrioritizesStagedScreeningWithoutRequiringLab(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');

        self::assertNotFalse($source);
        self::assertStringContainsString('StagedScreeningResultPresenter', $source);
        self::assertStringContainsString("tahap_screening", $source);
        self::assertStringContainsString("faktor_risiko_tersedia", $source);
        self::assertStringContainsString("hasil_deteksi.php?questionnaire_id=", $source);
        self::assertStringContainsString("kuesioner.php?questionnaire_id=", $source);
        self::assertStringNotContainsString('Lengkapi Data Laboratorium', $source);
        self::assertStringNotContainsString('simulasi regresi logistik', $source);
    }
}
