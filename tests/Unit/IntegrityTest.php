<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IntegrityTest extends TestCase
{
    public function testCertificateUsesDistinctRecentDaysThreshold(): void
    {
        self::assertFalse(isCertificateEligible(11));
        self::assertTrue(isCertificateEligible(12));
    }

    public function testTtdAndHaidHaveDatabaseInvariants(): void
    {
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        self::assertStringContainsString('uq_konsumsi_ttd_user_date', $schema);
        self::assertStringContainsString('uq_haid_one_active', $schema);
        self::assertStringContainsString('active_key INT GENERATED ALWAYS', $schema);
    }

    public function testMutationHandlersAreIdempotentAndTransactional(): void
    {
        $dashboard = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');
        self::assertStringContainsString('ON DUPLICATE KEY UPDATE', $dashboard);
        self::assertStringContainsString('beginTransaction', $dashboard);
        self::assertStringContainsString('FOR UPDATE', $dashboard);
    }

    public function testCertificateCountsDistinctDaysWithinWindow(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/cetak_sertifikat.php');
        self::assertStringContainsString('COUNT(DISTINCT tanggal)', $contents);
        self::assertStringContainsString('AKRAB_CERTIFICATE_WINDOW_DAYS', $contents);
        self::assertStringContainsString('isCertificateEligible', $contents);
    }
}
