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
                created_at TEXT NOT NULL,
                archived_at TEXT
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
             INSERT INTO kuesioner VALUES
                (1, 1, 12.5, 33, 85, 29, 20, 30, 24, 12, '2026-06-01 08:00:00', NULL),
                (2, 1, 12.7, 34, 86, 30, 30, 32, 28, 15, '2026-07-01 08:00:00', NULL),
                (3, 2, NULL, NULL, NULL, NULL, 40, 20, 20, 9, '2026-07-02 08:00:00', NULL),
                (4, 2, 11, 30, 75, 24, 90, 10, 10, 6, '2026-05-01 08:00:00', '2026-05-03');
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
        self::assertSame(2, $aggregate['responding_students']);
        self::assertSame(2, $aggregate['lab_available']);
        self::assertEqualsWithDelta(30.0, $aggregate['averages']['gejala'], 0.01);
    }

    public function testLatestStudentRowsAndHistoryStayWithinStudentBoundary(): void
    {
        $latest = $this->repository->latestByStudent();
        $history = $this->repository->historyForStudent(1);

        self::assertCount(2, $latest);
        $latestByStudent = array_column($latest, null, 'student_id');
        self::assertSame(2, (int) $latestByStudent[1]['questionnaire_id']);
        self::assertCount(2, $history);
        self::assertSame([1, 1], array_map(
            static fn (array $row): int => (int) $row['user_id'],
            $history
        ));
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
            $this->repository->latestByStudentForExport(),
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
            $this->repository->latestByStudentForExport(),
            null,
            'student_id'
        );
        self::assertNull($latestByStudent[1]['probabilitas_risiko']);
        self::assertNull($latestByStudent[1]['kategori_risiko']);

        $this->enableClinicalGate();
        $latestByStudent = array_column(
            $this->repository->latestByStudentForExport(),
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
