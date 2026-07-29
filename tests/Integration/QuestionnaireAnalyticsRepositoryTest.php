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
                (4, 2, 11, 30, 75, 24, 90, 10, 10, 6, '2026-05-01 08:00:00', '2026-05-03')"
        );

        $this->repository = new QuestionnaireAnalyticsRepository($this->pdo);
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
}
