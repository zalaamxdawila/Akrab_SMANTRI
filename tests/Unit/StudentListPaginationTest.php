<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StudentListPaginationTest extends TestCase
{
    public function testStudentListIsBoundedAndPreservesSearch(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/data_siswa.php');

        self::assertStringContainsString('$perPage = 25', $contents);
        self::assertStringContainsString('LIMIT ? OFFSET ?', $contents);
        self::assertStringContainsString('rawurlencode($search)', $contents);
        self::assertStringContainsString('$offset + 1', $contents);
    }
}
