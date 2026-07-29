<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SuperadminHbTtdGovernanceTest extends TestCase
{
    public function testHbAndTtdCorrectionPreserveBoundariesAndIntegrity(): void
    {
        $pdo = Sprint29Fixture::database();
        $service = new SuperadminHealthService($pdo);
        $service->correctHb(
            Sprint29Fixture::actor(), 1, '12.5', 'tidak_anemia',
            '2026-07-21', 'verification', 'h-1'
        );
        self::assertSame('tidak_anemia', $pdo->query(
            'SELECT kategori_anemia FROM kadar_hb WHERE id = 1'
        )->fetchColumn());
        $service->correctTtd(
            Sprint29Fixture::actor(), 1, '2026-07-21', 'belum',
            'correction', 't-1'
        );
        self::assertSame('belum', $pdo->query(
            'SELECT status_konsumsi FROM konsumsi_ttd WHERE id = 1'
        )->fetchColumn());
    }

    public function testLoginAsCannotMutateHealthMaster(): void
    {
        $this->expectException(DomainException::class);
        (new SuperadminHealthService(Sprint29Fixture::database()))->archive(
            new ActorContext(1, 2, 'superadmin', 'siswa', 9, 'support'),
            'kuesioner', 1, 'data_governance', 'x'
        );
    }

    public function testDuplicateTtdDateRollsBack(): void
    {
        $pdo = Sprint29Fixture::database();
        $pdo->exec("INSERT INTO konsumsi_ttd VALUES
            (2,2,'2026-07-21','sudah','2026-07-21',NULL,NULL,NULL,NULL,NULL,NULL)");
        try {
            (new SuperadminHealthService($pdo))->correctTtd(
                Sprint29Fixture::actor(), 1, '2026-07-21', 'belum',
                'correction', 'duplicate'
            );
            self::fail('Expected unique-date rejection.');
        } catch (PDOException) {
            self::assertSame('2026-07-20', $pdo->query(
                'SELECT tanggal FROM konsumsi_ttd WHERE id = 1'
            )->fetchColumn());
        }
    }
}
