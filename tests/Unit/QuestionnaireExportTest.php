<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/QuestionnaireExport.php';

final class QuestionnaireExportTest extends TestCase
{
    public function testCsvIsExcelCompatibleAndFormulaSafe(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        (new QuestionnaireExport())->writeCsv($stream, [[
            'student_id' => 7,
            'nama' => '=HYPERLINK("https://example.test")',
            'username' => '0001234567',
            'kelas' => 'X-A',
            'created_at' => '2026-08-10 09:30:00',
            'skor_gejala' => 20,
            'skor_makan' => 12,
            'skor_pengetahuan' => 30,
            'skor_sikap' => 32,
            'kadar_hb' => 11.8,
            'kadar_mchc' => null,
            'kadar_mcv' => 82,
            'kadar_mch' => 28,
            'probabilitas_risiko' => 0.425,
            'kategori_risiko' => 'sedang',
        ]]);

        rewind($stream);
        self::assertSame("\xEF\xBB\xBF", fread($stream, 3));
        $headers = fgetcsv($stream, null, ',', '"', '');
        $row = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        self::assertSame('ID Siswa', $headers[0]);
        self::assertSame('Kategori Risiko', $headers[array_key_last($headers)]);
        self::assertSame("'=HYPERLINK(\"https://example.test\")", $row[1]);
        self::assertSame("\t0001234567", $row[2]);
        self::assertSame('', $row[10]);
        self::assertSame('42,5%', $row[13]);
        self::assertSame('SEDANG', $row[14]);
    }

    public function testCsvNeutralizesFormulaMarkersAfterLeadingWhitespace(): void
    {
        self::assertSame("' \t=1+1", csvSafeCell(" \t=1+1"));
        self::assertSame("'\r@SUM(A1)", csvSafeCell("\r@SUM(A1)"));
    }

    public function testExportRoutesAreGuardedAuditedAndLinkedFromResultPages(): void
    {
        $root = dirname(__DIR__, 2);
        $uksExport = file_get_contents($root . '/uks/export_questionnaire.php');
        $superadminExport = file_get_contents(
            $root . '/superadmin/questionnaire_export.php'
        );

        self::assertStringContainsString("check_role('uks')", $uksExport);
        self::assertStringContainsString('SuperadminGuard::authorize', $superadminExport);
        self::assertStringContainsString('questionnaire.exported', $uksExport);
        self::assertStringContainsString('questionnaire.exported', $superadminExport);
        self::assertStringContainsString('QuestionnaireExport', $uksExport);
        self::assertStringContainsString('QuestionnaireExport', $superadminExport);
        self::assertStringContainsString('latestByStudentForExport', $uksExport);
        self::assertStringContainsString('latestByStudentForExport', $superadminExport);
        self::assertStringContainsString(
            'clinicalApprovalGatePassed()',
            file_get_contents(
                $root . '/app/Repositories/QuestionnaireAnalyticsRepository.php'
            )
        );
        self::assertLessThan(
            strpos($uksExport, 'questionnaire.exported'),
            strpos($uksExport, 'writeCsv')
        );
        self::assertLessThan(
            strpos($superadminExport, 'questionnaire.exported'),
            strpos($superadminExport, 'writeCsv')
        );

        self::assertStringContainsString(
            'export_questionnaire.php',
            file_get_contents($root . '/uks/hasil_kuesioner.php')
        );
        self::assertStringContainsString(
            'questionnaire_export.php',
            file_get_contents($root . '/superadmin/questionnaire_results.php')
        );

        $config = file_get_contents($root . '/config.php');
        self::assertStringContainsString('export_questionnaire.php', $config);
        self::assertStringContainsString('questionnaire_export.php', $config);
    }
}
