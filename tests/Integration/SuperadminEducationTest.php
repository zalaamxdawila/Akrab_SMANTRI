<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminEducationService.php';

final class SuperadminEducationTest extends TestCase
{
    public function testArticleCorrectionPreservesOriginalUksAndStoresTextAsData(): void
    {
        $pdo = Sprint30Fixture::database();
        (new SuperadminEducationService($pdo))->correctArticle(
            Sprint30Fixture::actor(), 1, '<script>alert(1)</script>', 'Isi <b>tebal</b>',
            'correction', 'e-1'
        );
        $row = $pdo->query('SELECT * FROM artikel_edukasi WHERE id = 1')->fetch();
        self::assertSame(3, $row['uks_id']);
        self::assertSame('<script>alert(1)</script>', $row['judul']);
        $audit = $pdo->query(
            'SELECT metadata_json FROM audit_log ORDER BY id DESC LIMIT 1'
        )->fetchColumn();
        self::assertStringNotContainsString('script', $audit);
    }

    public function testLoginAsCannotArchiveEducation(): void
    {
        $this->expectException(DomainException::class);
        (new SuperadminEducationService(Sprint30Fixture::database()))->archive(
            new ActorContext(1, 2, 'superadmin', 'siswa', 8, 'support'),
            'artikel_edukasi', 1, 'support', 'x'
        );
    }
}
