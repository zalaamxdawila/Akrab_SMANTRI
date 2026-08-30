<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningRouteContractTest extends TestCase
{
    public function testStudentRoutesFailClosedUntilMigrationIsRecorded(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['siswa/kuesioner.php', 'siswa/hasil_deteksi.php'] as $route) {
            $source = file_get_contents($root . '/' . $route);
            self::assertIsString($source);
            self::assertStringContainsString('PdoStagedScreeningStore::schemaIsReady($pdo)', $source);
            self::assertStringContainsString('http_response_code(503)', $source);
        }

        $preflight = file_get_contents($root . '/tools/preflight.php');
        self::assertIsString($preflight);
        self::assertStringContainsString('database_schema_021', $preflight);
        self::assertStringContainsString("021_staged_screening", $preflight);
    }

    public function testRouteImplementsServerControlledTwoStageFlow(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertNotFalse($route);
        self::assertStringContainsString("check_role('siswa')", $route);
        self::assertStringContainsString('verifyCsrfOrFail(csrfTokenFromRequest(', $route);
        self::assertStringContainsString('new StagedScreeningService(', $route);
        self::assertStringContainsString('new PdoStagedScreeningStore($pdo)', $route);
        self::assertStringContainsString('submitSymptoms(', $route);
        self::assertStringContainsString('submitRiskFactors(', $route);
        self::assertStringContainsString('pendingRiskFactors(', $route);
        self::assertStringContainsString('questionnaire_id', $route);
        self::assertStringContainsString("\$step === 'risk'", $route);
        self::assertStringContainsString("\$step === 'symptoms'", $route);
    }

    public function testFirstStageContainsOnlyProfileAndCanonicalSymptoms(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        foreach ([
            'Sahabat merasakan cepat lelah bila beraktivitas',
            'Sahabat merasakan pusing',
            'Sahabat merasakan mata berkunang-kunang',
            'bagian ujung tangan atau kaki sering dingin',
            'Sahabat merasakan suka sempoyongan',
            'berdebar-debar walaupun beraktivitas ringan',
            'Sahabat merasakan mengantuk',
            'Sahabat merasakan malas beraktivitas',
            'nafas terasa pendek waktu beraktivitas',
            'Sahabat merasakan pucat',
        ] as $question) {
            self::assertStringContainsString($question, $route);
        }

        self::assertStringNotContainsString('Hasil Lab Darah', $route);
        self::assertStringNotContainsString('Pengetahuan Dasar', $route);
        self::assertStringNotContainsString('Sikap terhadap anemia', $route);
    }

    public function testRiskStageUsesCanonicalMenstruationAndDietQuestions(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        foreach ([
            'Apakah sahabat sudah mengalami menstruasi',
            'Apakah siklus menstruasi sahabat teratur tiap bulan',
            'Isilah tabel berikut ini sesuai makanan yang sahabat makan setiap hari.',
            'Apakah sahabat ada sarapan setiap hari',
            'Apakah sahabat rutin makan siang',
            'Apakah sahabat selalu makan malam',
            'Apakah sahabat ada makan lagi atau snek menjelang tidur',
            'Tidak pernah',
        ] as $question) {
            self::assertStringContainsString($question, $route);
        }
    }

    public function testCopyDoesNotClaimExternalInstitutionalPartnership(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringNotContainsString('WHO', $route);
        self::assertStringContainsString('ahli medis sekolah', $route);
        self::assertStringContainsString('bukan diagnosis', $route);
        self::assertStringContainsString('tanpa pemeriksaan Hb', $route);
    }

    public function testLandingPageDescribesTheCurrentNoHbScreeningFlow(): void
    {
        $landing = (string) file_get_contents(dirname(__DIR__, 2) . '/index.php');

        self::assertStringContainsString('skrining bertahap berdasarkan gejala dan faktor risiko', $landing);
        self::assertStringContainsString('tanpa mewajibkan pemeriksaan Hb', $landing);
        self::assertStringContainsString('bukan diagnosis', $landing);
        self::assertStringNotContainsString('data laboratorium siswa', $landing);
        self::assertStringNotContainsString('WHO', $landing);
    }
}
