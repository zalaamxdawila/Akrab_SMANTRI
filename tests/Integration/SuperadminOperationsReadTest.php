<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2)
    . '/app/Repositories/SuperadminOperationalRepository.php';

final class SuperadminOperationsReadTest extends TestCase
{
    public function testListsAreFilteredPaginatedAndArchiveAware(): void
    {
        $pdo = Sprint30Fixture::database();
        $repository = new SuperadminOperationalRepository($pdo);
        self::assertSame(1, $repository->paginate(
            'article', 'Judul', false, 1, 25
        )['total']);
        $pdo->exec("UPDATE artikel_edukasi SET archived_at = '2026-07-29'");
        self::assertSame(0, $repository->paginate(
            'article', '', false, 1, 25
        )['total']);
        self::assertSame(1, $repository->paginate(
            'article', '', true, 1, 25
        )['total']);
    }

    public function testUnknownOperationalTypeFailsClosed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SuperadminOperationalRepository(Sprint30Fixture::database()))
            ->paginate('users', '', false, 1, 25);
    }
}
