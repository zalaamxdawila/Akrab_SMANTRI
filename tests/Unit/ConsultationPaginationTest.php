<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConsultationPaginationTest extends TestCase
{
    public function testUksConsultationListIsBoundedAndAvoidsNPlusOne(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/jawab_konsultasi.php');

        self::assertStringContainsString('$perPage = 20', $contents);
        self::assertStringContainsString('LIMIT :limit OFFSET :offset', $contents);
        self::assertStringContainsString('LEFT JOIN balasan_konsultasi', $contents);
        self::assertStringNotContainsString('SELECT isi_balasan', $contents);
    }
}
