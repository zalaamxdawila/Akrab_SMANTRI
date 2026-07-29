<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminConsultationService.php';

final class SuperadminConsultationTest extends TestCase
{
    public function testCorrectionPreservesOwnershipAndRedactsContentFromAudit(): void
    {
        $pdo = Sprint30Fixture::database();
        (new SuperadminConsultationService($pdo))->correctConsultation(
            Sprint30Fixture::actor(), 1, 'Pertanyaan dikoreksi', 'dijawab',
            'correction', 'c-1'
        );
        $row = $pdo->query('SELECT * FROM konsultasi WHERE id = 1')->fetch();
        self::assertSame(2, $row['siswa_id']);
        self::assertSame(3, $row['ahli_id']);
        $audit = $pdo->query(
            'SELECT metadata_json FROM audit_log ORDER BY id DESC LIMIT 1'
        )->fetchColumn();
        self::assertStringNotContainsString('Pertanyaan dikoreksi', $audit);
    }

    public function testArchivingConsultationArchivesReplyWithoutDeletingHistory(): void
    {
        $pdo = Sprint30Fixture::database();
        (new SuperadminConsultationService($pdo))->archiveConsultation(
            Sprint30Fixture::actor(), 1, 'data_governance', 'c-2'
        );
        self::assertSame(1, (int) $pdo->query(
            'SELECT COUNT(*) FROM konsultasi WHERE id = 1'
        )->fetchColumn());
        self::assertNotNull($pdo->query(
            'SELECT archived_at FROM balasan_konsultasi WHERE id = 1'
        )->fetchColumn());
    }

    public function testPendingStatusWithActiveReplyIsRejected(): void
    {
        $this->expectException(DomainException::class);
        (new SuperadminConsultationService(Sprint30Fixture::database()))
            ->correctConsultation(
                Sprint30Fixture::actor(), 1, 'Tetap ada', 'menunggu',
                'correction', 'inconsistent'
            );
    }
}
