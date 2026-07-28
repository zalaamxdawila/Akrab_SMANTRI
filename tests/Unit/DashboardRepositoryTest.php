<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DashboardRepositoryTest extends TestCase
{
    public function testUksDashboardDelegatesQueriesToRepository(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/dashboard.php');

        self::assertStringContainsString('new DashboardRepository($pdo)', $contents);
        self::assertStringNotContainsString('SELECT COUNT(*)', $contents);
        self::assertStringNotContainsString('SELECT kategori_risiko', $contents);
    }

    public function testRepositoryExposesBoundedReadModels(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/app/Repositories/DashboardRepository.php');

        self::assertStringContainsString('uksSummary', $contents);
        self::assertStringContainsString('ttdComplianceLastSevenDays', $contents);
        self::assertStringContainsString('max(0, $totalStudents - $count)', $contents);
    }
}
