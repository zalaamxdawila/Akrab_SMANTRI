<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminParentLinkService.php';

final class SuperadminParentLinkMutationTest extends TestCase
{
    public function testApproveArchiveAndRestoreAreAudited(): void
    {
        $pdo = Sprint28Fixture::database();
        $service = new SuperadminParentLinkService($pdo);
        $actor = Sprint28Fixture::actor();
        $service->apply($actor, 1, 'approve', 'siswa1', 'verification', 'a');
        self::assertSame('approved', $pdo->query(
            'SELECT status FROM parent_student_links WHERE id = 1'
        )->fetchColumn());
        $service->apply($actor, 1, 'archive', '', 'data_governance', 'b');
        self::assertNotFalse($pdo->query(
            'SELECT archived_at FROM parent_student_links WHERE id = 1'
        )->fetchColumn());
        $service->apply($actor, 1, 'restore', '', 'verification', 'c');
        self::assertNull($pdo->query(
            'SELECT archived_at FROM parent_student_links WHERE id = 1'
        )->fetchColumn());
        self::assertSame(3, (int) $pdo->query(
            "SELECT COUNT(*) FROM audit_log WHERE target_type = 'parent_student_link'"
        )->fetchColumn());
    }

    public function testForgedNonStudentTargetRollsBack(): void
    {
        $pdo = Sprint28Fixture::database();
        try {
            (new SuperadminParentLinkService($pdo))->apply(
                Sprint28Fixture::actor(), 1, 'approve', 'parent1', 'verification', 'x'
            );
            self::fail('Expected rejection.');
        } catch (DomainException) {
            self::assertSame('pending', $pdo->query(
                'SELECT status FROM parent_student_links WHERE id = 1'
            )->fetchColumn());
        }
    }
}
