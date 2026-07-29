<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SuperadminMenstruationGovernanceTest extends TestCase
{
    public function testOneActiveCycleAndArchiveExclusionArePreserved(): void
    {
        $pdo = Sprint29Fixture::database();
        $pdo->exec("INSERT INTO riwayat_haid VALUES
            (2,2,'2026-06-01','2026-06-05',NULL,NULL,NULL,NULL,NULL,NULL)");
        $service = new SuperadminHealthService($pdo);
        $service->correctMenstruation(
            Sprint29Fixture::actor(), 2, '2026-06-02', '2026-06-06',
            'correction', 'm-1'
        );
        self::assertSame(1, (int) $pdo->query(
            'SELECT COUNT(*) FROM riwayat_haid
             WHERE user_id = 2 AND tanggal_selesai IS NULL AND archived_at IS NULL'
        )->fetchColumn());
        $service->archive(
            Sprint29Fixture::actor(), 'riwayat_haid', 1,
            'data_governance', 'm-2'
        );
        self::assertSame(1, (int) $pdo->query(
            'SELECT COUNT(*) FROM riwayat_haid WHERE archived_at IS NULL'
        )->fetchColumn());
    }

    public function testSecondActiveCycleIsRejectedAndRolledBack(): void
    {
        $pdo = Sprint29Fixture::database();
        $pdo->exec("INSERT INTO riwayat_haid VALUES
            (2,2,'2026-06-01','2026-06-05',NULL,NULL,NULL,NULL,NULL,NULL)");
        try {
            (new SuperadminHealthService($pdo))->correctMenstruation(
                Sprint29Fixture::actor(), 2, '2026-06-02', null,
                'correction', 'active-conflict'
            );
            self::fail('Expected active-cycle rejection.');
        } catch (DomainException) {
            self::assertSame('2026-06-05', $pdo->query(
                'SELECT tanggal_selesai FROM riwayat_haid WHERE id = 2'
            )->fetchColumn());
        }
    }
}
