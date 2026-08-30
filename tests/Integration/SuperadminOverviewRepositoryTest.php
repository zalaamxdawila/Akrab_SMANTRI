<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2)
    . '/app/Repositories/SuperadminOverviewRepository.php';

final class SuperadminOverviewRepositoryTest extends TestCase
{
    public function testSummaryReturnsBoundedMasterMetrics(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('CREATE TABLE users (id INTEGER, role TEXT, status TEXT)');
        $pdo->exec('CREATE TABLE parent_student_links (status TEXT)');
        $pdo->exec('CREATE TABLE konsultasi (status TEXT)');
        $pdo->exec('CREATE TABLE artikel_edukasi (id INTEGER)');
        $pdo->exec('CREATE TABLE konsumsi_ttd (tanggal TEXT, status_konsumsi TEXT)');
        $pdo->exec('CREATE TABLE kuesioner (id INTEGER, archived_at TEXT, history_only_at TEXT)');
        $pdo->exec('CREATE TABLE kadar_hb (id INTEGER)');
        $pdo->exec(
            'CREATE TABLE schema_migrations (
                version TEXT,
                applied_at TEXT
            )'
        );
        $pdo->exec(
            "INSERT INTO users VALUES
                (1, 'superadmin', 'active'),
                (2, 'siswa', 'active'),
                (3, 'siswa', 'inactive'),
                (4, 'uks', 'active'),
                (5, 'orangtua', 'archived')"
        );
        $pdo->exec(
            "INSERT INTO parent_student_links VALUES
                ('pending'), ('approved'), ('approved')"
        );
        $pdo->exec(
            "INSERT INTO konsultasi VALUES ('menunggu'), ('dijawab')"
        );
        $pdo->exec('INSERT INTO artikel_edukasi VALUES (1), (2)');
        $pdo->exec(
            "INSERT INTO konsumsi_ttd VALUES
                ('2026-07-29', 'sudah'), ('2026-07-29', 'belum')"
        );
        $pdo->exec('INSERT INTO kuesioner VALUES (1, NULL, NULL), (2, NULL, NULL), (3, NULL, NULL)');
        $pdo->exec('INSERT INTO kadar_hb VALUES (1)');
        $pdo->exec(
            "INSERT INTO schema_migrations VALUES
                ('008_superadmin_identity', '2026-07-28 10:00:00'),
                ('009_impersonation_audit', '2026-07-29 10:00:00')"
        );

        $summary = (new SuperadminOverviewRepository($pdo))->summary(
            '2026-07-29'
        );

        self::assertSame(1, $summary['accounts']['siswa']['active']);
        self::assertSame(1, $summary['accounts']['siswa']['inactive']);
        self::assertSame(1, $summary['accounts']['orangtua']['archived']);
        self::assertSame(2, $summary['parent_links']['approved']);
        self::assertSame(1, $summary['operations']['consultations_pending']);
        self::assertSame(2, $summary['operations']['articles']);
        self::assertSame(1, $summary['operations']['ttd_confirmed_today']);
        self::assertSame(3, $summary['health']['questionnaires']);
        self::assertSame(1, $summary['health']['hb_records']);
        self::assertSame(
            '009_impersonation_audit',
            $summary['migration_version']
        );
    }
}
