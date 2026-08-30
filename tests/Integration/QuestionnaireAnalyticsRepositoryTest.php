<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireAnalyticsRepositoryTest extends TestCase
{
    private PDO $pdo;
    private QuestionnaireAnalyticsRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                nama TEXT NOT NULL,
                username TEXT NOT NULL,
                kelas TEXT,
                role TEXT NOT NULL,
                status TEXT NOT NULL
            );
            CREATE TABLE parent_student_links (
                id INTEGER PRIMARY KEY,
                parent_id INTEGER NOT NULL,
                student_id INTEGER,
                status TEXT NOT NULL,
                archived_at TEXT
            );
            CREATE TABLE kuesioner (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                kadar_hb REAL,
                kadar_mchc REAL,
                kadar_mcv REAL,
                kadar_mch REAL,
                skor_gejala INTEGER NOT NULL,
                skor_sikap INTEGER NOT NULL,
                skor_pengetahuan INTEGER NOT NULL,
                skor_makan INTEGER NOT NULL,
                answers_snapshot TEXT,
                tanggal_lahir TEXT,
                jenis_kelamin TEXT,
                pendidikan TEXT,
                tahap_screening TEXT,
                rerata_gejala REAL,
                persentase_faktor_risiko REAL,
                hasil_screening TEXT,
                versi_screening TEXT,
                created_at TEXT NOT NULL,
                archived_at TEXT,
                history_only_at TEXT,
                history_only_by INTEGER,
                history_only_reason TEXT
            );
            CREATE TABLE hasil_deteksi (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                probabilitas_risiko REAL NOT NULL,
                kategori_risiko TEXT NOT NULL,
                questionnaire_id INTEGER,
                tanggal TEXT NOT NULL,
                created_at TEXT NOT NULL,
                archived_at TEXT
            )"
        );
        $this->pdo->exec(
            "INSERT INTO users VALUES
                (1, 'Siswa Satu', 'satu', 'X-A', 'siswa', 'active'),
                (2, 'Siswa Dua', 'dua', 'X-B', 'siswa', 'active'),
                (3, 'Wali Satu', 'wali', NULL, 'orangtua', 'active');
             INSERT INTO parent_student_links VALUES
                (1, 3, 1, 'approved', NULL);
             INSERT INTO kuesioner (
                id, user_id, kadar_hb, kadar_mchc, kadar_mcv, kadar_mch,
                skor_gejala, skor_sikap, skor_pengetahuan, skor_makan,
                answers_snapshot, created_at, archived_at
             ) VALUES
                (1, 1, 12.5, 33, 85, 29, 20, 30, 24, 12, NULL, '2026-06-01 08:00:00', NULL),
                (2, 1, 12.7, 34, 86, 30, 30, 32, 28, 15, 'snapshot-test', '2026-07-01 08:00:00', NULL),
                (3, 2, NULL, NULL, NULL, NULL, 40, 20, 20, 9, NULL, '2026-07-02 08:00:00', NULL),
                (4, 2, 11, 30, 75, 24, 90, 10, 10, 6, NULL, '2026-05-01 08:00:00', '2026-05-03');
             INSERT INTO hasil_deteksi VALUES
                (1, 1, 0.2500, 'rendah', 1, '2026-06-01', '2026-06-01 08:00:01', NULL)"
        );

        $this->repository = new QuestionnaireAnalyticsRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        foreach ([
            'CLINICAL_RISK_ENABLED',
            'CLINICAL_OWNER_APPROVED',
            'CLINICAL_MODEL_APPROVED',
            'CLINICAL_SPEC_VERSION',
            'CLINICAL_MODEL_VERSION',
            'CLINICAL_MODEL_CHECKSUM',
        ] as $name) {
            putenv($name);
        }
    }

    public function testAggregateUsesAllNonArchivedResponses(): void
    {
        $aggregate = $this->repository->aggregate();

        self::assertSame(3, $aggregate['total_responses']);
        self::assertSame(2, $aggregate['total_students']);
        self::assertSame(2, $aggregate['responding_students']);
        self::assertSame(0, $aggregate['not_responded_students']);
        self::assertSame(2, $aggregate['lab_available']);
        self::assertSame(3, $aggregate['legacy_responses']);
        self::assertSame(0, $aggregate['staged']['responses']);
        self::assertEqualsWithDelta(30.0, $aggregate['averages']['gejala'], 0.01);
    }

    public function testStagedRowsAreSeparatedFromLegacyAverages(): void
    {
        $this->pdo->exec(
            "INSERT INTO kuesioner (
                id, user_id, skor_gejala, skor_sikap, skor_pengetahuan, skor_makan,
                answers_snapshot, tahap_screening, rerata_gejala,
                persentase_faktor_risiko, hasil_screening, versi_screening,
                created_at, archived_at
             ) VALUES (
                5, 2, 46, 0, 0, 0, '{}', 'gejala_selesai', 4.6,
                NULL, 'gejala_di_bawah_ambang', '2026-08-30.staged-v1',
                '2026-08-30 08:00:00', NULL
             )"
        );

        $aggregate = $this->repository->aggregate();
        self::assertSame(4, $aggregate['total_responses']);
        self::assertSame(3, $aggregate['legacy_responses']);
        self::assertSame(1, $aggregate['staged']['responses']);
        self::assertSame(0, $aggregate['staged']['completed']);
        self::assertEqualsWithDelta(4.6, $aggregate['staged']['avg_symptom'], 0.01);
        self::assertEqualsWithDelta(30.0, $aggregate['averages']['gejala'], 0.01);
        self::assertEqualsWithDelta(12.0, $aggregate['averages']['makan'], 0.01);

        $staged = array_column(
            $this->repository->latestStagedByStudent(),
            null,
            'student_id'
        );
        $legacy = array_column(
            $this->repository->latestLegacyByStudent(),
            null,
            'student_id'
        );

        self::assertCount(1, $staged);
        self::assertSame(5, (int) $staged[2]['questionnaire_id']);
        self::assertSame('gejala_selesai', $staged[2]['tahap_screening']);
        self::assertSame('2026-08-30.staged-v1', $staged[2]['versi_screening']);
        self::assertCount(2, $legacy);
        self::assertSame(2, (int) $legacy[1]['questionnaire_id']);
        self::assertSame(3, (int) $legacy[2]['questionnaire_id']);
    }

    public function testLatestStudentRowsAndHistoryStayWithinStudentBoundary(): void
    {
        $latest = $this->repository->latestLegacyByStudent();
        $history = $this->repository->historyForStudent(1);

        self::assertCount(2, $latest);
        $latestByStudent = array_column($latest, null, 'student_id');
        self::assertSame(2, (int) $latestByStudent[1]['questionnaire_id']);
        self::assertSame('snapshot-test', $latestByStudent[1]['answers_snapshot']);
        self::assertCount(2, $history);
        self::assertSame([1, 1], array_map(
            static fn (array $row): int => (int) $row['user_id'],
            $history
        ));
    }

    public function testPrimaryQuestionnaireLookupStaysWithinStudentBoundary(): void
    {
        self::assertSame(
            2,
            (int) $this->repository->primaryQuestionnaireForStudent(1, 2)['id']
        );
        self::assertNull($this->repository->primaryQuestionnaireForStudent(2, 2));
        self::assertNull($this->repository->primaryQuestionnaireForStudent(1, 999));

        $this->pdo->exec(
            "UPDATE kuesioner SET history_only_at = '2026-08-30 09:00:00' WHERE id = 2"
        );
        self::assertNull($this->repository->primaryQuestionnaireForStudent(1, 2));
    }

    public function testHistoryOnlyRowsStayPersonalAndLeavePrimaryAnalytics(): void
    {
        $this->pdo->exec(
            "UPDATE kuesioner
             SET history_only_at = '2026-08-30 09:00:00',
                 history_only_by = 99,
                 history_only_reason = 'Pengisian perlu diulang.'
             WHERE id = 2"
        );

        $aggregate = $this->repository->aggregate();
        $latest = array_column(
            $this->repository->latestLegacyByStudent(),
            null,
            'student_id'
        );
        $history = $this->repository->historyForStudent(1);

        self::assertSame(2, $aggregate['total_responses']);
        self::assertSame(1, (int) $latest[1]['questionnaire_id']);
        self::assertSame(1, (int) $this->repository->latestPrimaryForStudent(1)['id']);
        self::assertCount(2, $history);
        self::assertSame('Pengisian perlu diulang.', $history[1]['history_only_reason']);
    }

    public function testParentCanResolveOnlyAnApprovedNonArchivedStudentLink(): void
    {
        $student = $this->repository->approvedStudentForParent(3);
        self::assertSame(1, (int) $student['id']);

        $this->pdo->exec(
            "UPDATE parent_student_links SET archived_at = '2026-07-03' WHERE id = 1"
        );
        self::assertNull($this->repository->approvedStudentForParent(3));
    }

    public function testDetectionMustBelongToTheCurrentQuestionnaireWindow(): void
    {
        self::assertNull($this->repository->latestDetectionForStudent(1, 0));
        self::assertNull($this->repository->latestDetectionForStudent(1, 2));

        $this->pdo->exec(
            "INSERT INTO hasil_deteksi VALUES
                (2, 1, 0.4000, 'sedang', 2, '2026-07-01', '2026-07-01 08:00:01', NULL)"
        );

        self::assertNull($this->repository->latestDetectionForStudent(1, 2));

        $this->enableClinicalGate();
        $current = $this->repository->latestDetectionForStudent(1, 2);
        self::assertSame(2, (int) $current['id']);
    }

    public function testLatestStudentRowsIncludeOnlyCurrentRiskResult(): void
    {
        $latestByStudent = array_column(
            $this->repository->latestLegacyByStudentForExport(),
            null,
            'student_id'
        );

        self::assertNull($latestByStudent[1]['kategori_risiko']);
        self::assertNull($latestByStudent[2]['kategori_risiko']);

        $this->pdo->exec(
            "INSERT INTO hasil_deteksi VALUES
                (2, 1, 0.4000, 'sedang', 2, '2026-07-01', '2026-07-01 08:00:01', NULL)"
        );

        $latestByStudent = array_column(
            $this->repository->latestLegacyByStudentForExport(),
            null,
            'student_id'
        );
        self::assertNull($latestByStudent[1]['probabilitas_risiko']);
        self::assertNull($latestByStudent[1]['kategori_risiko']);

        $this->enableClinicalGate();
        $latestByStudent = array_column(
            $this->repository->latestLegacyByStudentForExport(),
            null,
            'student_id'
        );
        self::assertEqualsWithDelta(
            0.4,
            (float) $latestByStudent[1]['probabilitas_risiko'],
            0.0001
        );
        self::assertSame('sedang', $latestByStudent[1]['kategori_risiko']);

    }

    private function enableClinicalGate(): void
    {
        putenv('CLINICAL_RISK_ENABLED=true');
        putenv('CLINICAL_OWNER_APPROVED=true');
        putenv('CLINICAL_MODEL_APPROVED=true');
        putenv('CLINICAL_SPEC_VERSION=spec-v1');
        putenv('CLINICAL_MODEL_VERSION=model-v1');
        putenv('CLINICAL_MODEL_CHECKSUM=' . str_repeat('a', 64));
    }
}
