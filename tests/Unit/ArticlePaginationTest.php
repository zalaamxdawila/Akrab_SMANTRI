<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ArticlePaginationTest extends TestCase
{
    public function testOwnedArticleListIsBounded(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/kelola_artikel.php');

        self::assertStringContainsString('$perPage = 15', $contents);
        self::assertStringContainsString('WHERE uks_id = ?', $contents);
        self::assertStringContainsString('LIMIT ? OFFSET ?', $contents);
        self::assertStringContainsString('ORDER BY tanggal_publikasi DESC, id DESC', $contents);
    }
}
