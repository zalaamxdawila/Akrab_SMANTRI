<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminNotificationService.php';

final class SuperadminNotificationTest extends TestCase
{
    public function testScheduleCorrectionValidatesTimeEnumAndPreservesOwner(): void
    {
        $pdo = Sprint30Fixture::database();
        (new SuperadminNotificationService($pdo))->correctSchedule(
            Sprint30Fixture::actor(), 1, '08:30', 'mingguan', false,
            'correction', 'n-1'
        );
        $row = $pdo->query('SELECT * FROM jadwal_notifikasi WHERE id = 1')->fetch();
        self::assertSame(2, $row['siswa_id']);
        self::assertSame('mingguan', $row['hari']);
    }

    public function testDeliveryLogOnlyAllowsConfirmationCorrection(): void
    {
        $pdo = Sprint30Fixture::database();
        (new SuperadminNotificationService($pdo))->correctDeliveryConfirmation(
            Sprint30Fixture::actor(), 1, true, 'verification', 'n-2'
        );
        $row = $pdo->query('SELECT * FROM log_notifikasi WHERE id = 1')->fetch();
        self::assertSame('sukses', $row['status_terkirim']);
        self::assertSame(1, $row['sudah_dikonfirmasi']);
    }
}
