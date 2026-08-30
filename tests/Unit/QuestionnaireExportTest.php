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

        (new QuestionnaireExport())->writeLegacyCsv($stream, [[
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
        self::assertCount(51, $headers);
        self::assertSame('', $row[46]);
        self::assertSame('42,5%', $row[49]);
        self::assertSame('SEDANG', $row[50]);
    }

    public function testCsvExportsEveryQuestionnaireAnswerInItsOwnColumn(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        $snapshot = json_encode([
            'version' => '2026-08-17.v1',
            'sections' => [
                'makan' => ['items' => [
                    ['key' => 'makan_1', 'question' => 'Sarapan pagi', 'answer' => 'Selalu'],
                ]],
                'gejala' => ['items' => [
                    ['key' => 'gejala_1', 'question' => 'Cepat lelah bila beraktivitas', 'answer' => '4 dari 10'],
                    ['key' => 'gejala_2', 'question' => 'Merasa pusing', 'answer' => '5 dari 10'],
                ]],
                'sikap' => ['items' => [
                    ['key' => 'sikap_1', 'question' => 'Anemia merupakan kondisi sel darah merah di bawah normal', 'answer' => 'Setuju'],
                ]],
                'pengetahuan' => ['items' => [
                    ['key' => 'pengetahuan_1', 'question' => 'Zat gizi apa yang menjadi penyebab utama anemia?', 'answer' => 'Zat Besi (Fe)'],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        (new QuestionnaireExport())->writeLegacyCsv($stream, [[
            'student_id' => 8,
            'nama' => 'Siti',
            'username' => '00123',
            'kelas' => 'X-B',
            'created_at' => '2026-08-14 10:00:00',
            'answers_snapshot' => $snapshot,
            'skor_gejala' => 9,
            'skor_makan' => 3,
            'skor_pengetahuan' => 10,
            'skor_sikap' => 3,
        ]]);

        rewind($stream);
        fread($stream, 3);
        $headers = fgetcsv($stream, null, ',', '"', '');
        $row = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        self::assertCount(51, $headers);
        self::assertSame('Pola Makan 1 - Sarapan pagi', $headers[5]);
        self::assertSame('Gejala 1 - Cepat lelah bila beraktivitas', $headers[11]);
        self::assertSame('Gejala 2 - Merasa pusing', $headers[12]);
        self::assertSame('Pengetahuan 1 - Apakah sahabat tahu tentang anemia?', $headers[31]);
        self::assertSame('Selalu', $row[5]);
        self::assertSame('4', $row[11]);
        self::assertSame('5', $row[12]);
        self::assertSame('Setuju', $row[21]);
        self::assertSame('Zat Besi (Fe)', $row[31]);
        self::assertSame('9', $row[41]);
    }

    public function testCsvLeavesPerQuestionAnswersBlankForLegacyOrInvalidSnapshots(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        (new QuestionnaireExport())->writeLegacyCsv($stream, [
            ['student_id' => 1, 'answers_snapshot' => null],
            ['student_id' => 2, 'answers_snapshot' => '{invalid-json'],
        ]);

        rewind($stream);
        fread($stream, 3);
        fgetcsv($stream, null, ',', '"', '');
        $legacyRow = fgetcsv($stream, null, ',', '"', '');
        $invalidRow = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        self::assertSame(array_fill(0, 36, ''), array_slice($legacyRow, 5, 36));
        self::assertSame(array_fill(0, 36, ''), array_slice($invalidRow, 5, 36));
    }

    public function testStagedCsvHasItsOwnProfileAndScreeningColumns(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        $snapshot = json_encode([
            'version' => '2026-08-30.staged-v1',
            'profile' => [
                'tanggal_lahir' => '2010-05-12',
                'usia' => 16,
                'pendidikan' => 'Kelas X',
                'jenis_kelamin' => 'perempuan',
            ],
            'sections' => [
                'gejala' => ['items' => [
                    ['key' => 'gejala_1', 'answer' => '5 dari 10'],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        (new QuestionnaireExport())->writeStagedCsv($stream, [[
            'student_id' => 9,
            'nama' => 'Ayu',
            'username' => '009',
            'pendidikan' => 'Kelas X',
            'tanggal_lahir' => '2010-05-12',
            'jenis_kelamin' => 'perempuan',
            'created_at' => '2026-08-30 08:00:00',
            'versi_screening' => 'staged-v1',
            'tahap_screening' => 'gejala_selesai',
            'rerata_gejala' => 4.6,
            'hasil_screening' => 'gejala_di_bawah_ambang',
            'answers_snapshot' => $snapshot,
        ]]);

        rewind($stream);
        fread($stream, 3);
        $headers = fgetcsv($stream, null, ',', '"', '');
        $row = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        self::assertCount(39, $headers);
        self::assertSame('Kelas Saat Pengisian', $headers[3]);
        self::assertSame('Tanggal Lahir', $headers[4]);
        self::assertSame('Usia Saat Pengisian', $headers[5]);
        self::assertSame('Jenis Kelamin', $headers[6]);
        self::assertSame('Gejala 1 - Cepat lelah bila beraktivitas', $headers[9]);
        self::assertSame('Rerata Gejala (0-10)', $headers[35]);
        self::assertSame('Hasil Skrining', $headers[37]);
        self::assertSame('Kelas X', $row[3]);
        self::assertSame('16 tahun', $row[5]);
        self::assertSame('Perempuan', $row[6]);
        self::assertSame('TAHAP GEJALA SELESAI', $row[8]);
        self::assertSame('5', $row[9]);
        self::assertSame('4.6', $row[35]);
        self::assertSame('', $row[36]);
        self::assertSame('GEJALA DI BAWAH AMBANG', $row[37]);
        self::assertNotContains('Hb (g/dL)', $headers);
        self::assertNotContains('Skor Pengetahuan (0-48)', $headers);
    }

    public function testStagedCsvExportsCompletedRiskAnswersAndScore(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        $snapshot = json_encode([
            'sections' => [
                'menstruasi' => ['items' => [
                    ['key' => 'mens_sudah', 'answer' => 'Sudah'],
                ]],
                'makan' => ['items' => [
                    ['key' => 'tabel_makan_pagi', 'answer' => '=cmd|test'],
                    ['key' => 'makan_1', 'answer' => 'Selalu'],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        (new QuestionnaireExport())->writeStagedCsv($stream, [[
            'student_id' => 10,
            'answers_snapshot' => $snapshot,
            'tahap_screening' => 'selesai',
            'rerata_gejala' => 6.2,
            'persentase_faktor_risiko' => 74.9,
            'hasil_screening' => 'terindikasi_anemia',
        ]]);

        rewind($stream);
        fread($stream, 3);
        fgetcsv($stream, null, ',', '"', '');
        $row = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        self::assertSame('SKRINING SELESAI', $row[8]);
        self::assertSame('Sudah', $row[19]);
        self::assertSame("'=cmd|test", $row[24]);
        self::assertSame('Selalu', $row[29]);
        self::assertSame('74.9', $row[36]);
        self::assertSame('TERINDIKASI RISIKO ANEMIA', $row[37]);
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
        self::assertStringContainsString('latestStagedByStudentForExport', $uksExport);
        self::assertStringContainsString('latestLegacyByStudentForExport', $uksExport);
        self::assertStringContainsString('latestStagedByStudentForExport', $superadminExport);
        self::assertStringContainsString('latestLegacyByStudentForExport', $superadminExport);
        self::assertStringContainsString('writeStagedCsv', $uksExport);
        self::assertStringContainsString('writeLegacyCsv', $uksExport);
        self::assertStringContainsString('writeStagedCsv', $superadminExport);
        self::assertStringContainsString('writeLegacyCsv', $superadminExport);
        self::assertStringContainsString(
            'modelExecutionGatePassed()',
            file_get_contents(
                $root . '/app/Repositories/QuestionnaireAnalyticsRepository.php'
            )
        );
        self::assertLessThan(
            strpos($uksExport, 'questionnaire.exported'),
            strpos($uksExport, 'writeStagedCsv')
        );
        self::assertLessThan(
            strpos($superadminExport, 'questionnaire.exported'),
            strpos($superadminExport, 'writeStagedCsv')
        );

        self::assertStringContainsString(
            'export_questionnaire.php',
            file_get_contents($root . '/uks/hasil_kuesioner.php')
        );
        self::assertStringContainsString(
            'questionnaire_export.php',
            file_get_contents($root . '/superadmin/questionnaire_results.php')
        );

        $uksResults = file_get_contents($root . '/uks/hasil_kuesioner.php');
        $superadminResults = file_get_contents(
            $root . '/superadmin/questionnaire_results.php'
        );
        foreach ([$uksResults, $superadminResults] as $resultsPage) {
            self::assertStringContainsString('Hasil Skrining Baru', $resultsPage);
            self::assertStringContainsString('Hasil Kuesioner Lama', $resultsPage);
            self::assertStringContainsString('scope="col"', $resultsPage);
            self::assertStringContainsString('visually-hidden">Aksi', $resultsPage);
        }
        self::assertStringContainsString('type=baru', $uksResults);
        self::assertStringContainsString('type=lama', $uksResults);
        self::assertStringContainsString('value="baru"', $superadminResults);
        self::assertStringContainsString('value="lama"', $superadminResults);
        self::assertStringContainsString('questionnaire_id=', $uksResults);
        self::assertStringContainsString('questionnaire_id=', $superadminResults);
        self::assertStringContainsString(
            'primaryQuestionnaireForStudent',
            file_get_contents($root . '/uks/detail_siswa.php')
        );
        self::assertStringContainsString(
            'primaryQuestionnaireForStudent',
            $superadminResults
        );

        $config = file_get_contents($root . '/config.php');
        self::assertStringContainsString('export_questionnaire.php', $config);
        self::assertStringContainsString('questionnaire_export.php', $config);
    }
}
