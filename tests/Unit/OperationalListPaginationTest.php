<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OperationalListPaginationTest extends TestCase
{
    public function testRemainingOperationalListsAreBounded(): void
    {
        $studentHistory = file_get_contents(dirname(__DIR__, 2) . '/siswa/konsultasi.php');
        $parentLinks = file_get_contents(dirname(__DIR__, 2) . '/uks/kelola_tautan.php');

        self::assertStringContainsString('$perPage = 10', $studentHistory);
        self::assertStringContainsString('LIMIT ? OFFSET ?', $studentHistory);
        self::assertStringContainsString('$perPage = 25', $parentLinks);
        self::assertStringContainsString('LIMIT ? OFFSET ?', $parentLinks);
    }
}
