<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/config/csv.php';
require_once dirname(__DIR__, 2) . '/app/Repositories/SuperadminReportRepository.php';

final class SuperadminReportExportTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY, nama TEXT, username TEXT, password TEXT,
            role TEXT, status TEXT, kelas TEXT, created_at TEXT
        )');
        $this->pdo->exec("INSERT INTO users VALUES
            (1,'=Master','master','secret','superadmin','active',NULL,'2026-01-01'),
            (2,'Siswa','siswa','secret','siswa','active','7A','2026-01-02'),
            (3,'Arsip','arsip','secret','siswa','archived','7B','2026-01-03')");
    }

    public function testReportAggregatesAndExportExcludeArchivedAndSecrets(): void
    {
        $repository = new SuperadminReportRepository($this->pdo);
        $report = $repository->paginate('', 1, 25);
        self::assertSame(2, $report['total']);
        $rows = $repository->exportRows('');
        self::assertCount(2, $rows);
        self::assertArrayNotHasKey('password', $rows[0]);
        self::assertSame([1, 2], array_map('intval', array_column($rows, 'id')));
    }

    public function testCsvFormulaInjectionIsNeutralized(): void
    {
        self::assertSame("'=2+2", csvSafeCell('=2+2'));
        self::assertSame("'@SUM(A1)", csvSafeCell('@SUM(A1)'));
        self::assertSame('normal', csvSafeCell('normal'));
    }

    public function testUnknownRoleFailsClosed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SuperadminReportRepository($this->pdo))->exportRows('root');
    }
}
