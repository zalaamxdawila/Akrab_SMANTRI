<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2)
    . '/app/Repositories/SuperadminUserRepository.php';

final class SuperadminUserReadTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                nama TEXT,
                role TEXT,
                status TEXT,
                username TEXT,
                password_hash TEXT,
                kelas TEXT,
                created_at TEXT
            )'
        );
        foreach (['kuesioner', 'hasil_deteksi', 'konsumsi_ttd'] as $table) {
            $this->pdo->exec(
                "CREATE TABLE {$table} (id INTEGER, user_id INTEGER)"
            );
        }
        $this->pdo->exec(
            'CREATE TABLE konsultasi (id INTEGER, siswa_id INTEGER)'
        );
        $this->pdo->exec(
            "INSERT INTO users VALUES
                (1, 'Master', 'superadmin', 'active', 'master', 'hash-1', NULL, '2026-01-01'),
                (2, 'Siti Amanah', 'siswa', 'active', 'siti', 'hash-2', 'VIII A', '2026-02-01'),
                (3, 'Siti Lama', 'siswa', 'archived', 'siti_lama', 'hash-3', 'IX B', '2026-03-01'),
                (4, 'UKS Satu', 'uks', 'active', 'uks1', 'hash-4', NULL, '2026-04-01')"
        );
        $this->pdo->exec('INSERT INTO kuesioner VALUES (1, 2), (2, 2)');
        $this->pdo->exec('INSERT INTO hasil_deteksi VALUES (1, 2)');
        $this->pdo->exec('INSERT INTO konsumsi_ttd VALUES (1, 2)');
        $this->pdo->exec('INSERT INTO konsultasi VALUES (1, 2)');
    }

    public function testListIsFilteredPaginatedAndNeverReturnsPasswordHash(): void
    {
        $result = (new SuperadminUserRepository($this->pdo))->paginate(
            'Siti',
            'siswa',
            'active',
            1,
            25
        );

        self::assertSame(1, $result['total']);
        self::assertSame('Siti Amanah', $result['items'][0]['nama']);
        self::assertArrayNotHasKey('password_hash', $result['items'][0]);
    }

    public function testDetailReturnsSafeFieldsAndAggregateCounts(): void
    {
        $repository = new SuperadminUserRepository($this->pdo);
        $detail = $repository->findDetail(2);

        self::assertSame('siti', $detail['username']);
        self::assertSame(2, $detail['record_counts']['questionnaires']);
        self::assertSame(1, $detail['record_counts']['consultations']);
        self::assertArrayNotHasKey('password_hash', $detail);
        self::assertNull($repository->findDetail(999));
    }

    public function testUnknownFiltersFailClosed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SuperadminUserRepository($this->pdo))->paginate(
            '',
            'admin',
            'active',
            1,
            25
        );
    }
}
