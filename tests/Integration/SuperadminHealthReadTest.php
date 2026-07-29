<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Repositories/SuperadminHealthRepository.php';

final class SuperadminHealthReadTest extends TestCase
{
    public function testSearchPaginationAndArchiveVisibilityAreBounded(): void
    {
        $pdo = Sprint29Fixture::database();
        $repository = new SuperadminHealthRepository($pdo);
        $active = $repository->paginate('questionnaire', 'Siswa', false, 1, 25);
        self::assertSame(1, $active['total']);
        self::assertArrayNotHasKey('alamat', $active['items'][0]);

        $pdo->exec("UPDATE kuesioner SET archived_at = '2026-07-29' WHERE id = 1");
        self::assertSame(0, $repository->paginate(
            'questionnaire', '', false, 1, 25
        )['total']);
        self::assertSame(1, $repository->paginate(
            'questionnaire', '', true, 1, 25
        )['total']);
    }

    public function testUnknownRecordTypeFailsClosed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SuperadminHealthRepository(Sprint29Fixture::database()))
            ->paginate('users', '', false, 1, 25);
    }
}
