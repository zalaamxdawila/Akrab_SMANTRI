<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Repositories/SuperadminParentLinkRepository.php';

final class SuperadminParentLinkReadTest extends TestCase
{
    public function testArchiveIsHiddenUnlessExplicitlyFiltered(): void
    {
        $pdo = Sprint28Fixture::database();
        $repository = new SuperadminParentLinkRepository($pdo);
        self::assertSame(1, $repository->paginate('', '', false, 1, 25)['total']);
        $pdo->exec("UPDATE parent_student_links SET archived_at = '2026-07-29' WHERE id = 1");
        self::assertSame(0, $repository->paginate('', '', false, 1, 25)['total']);
        self::assertSame(1, $repository->paginate('', '', true, 1, 25)['total']);
    }
}
