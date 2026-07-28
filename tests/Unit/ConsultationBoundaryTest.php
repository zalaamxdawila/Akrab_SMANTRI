<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConsultationBoundaryTest extends TestCase
{
    public function testConsultationPagesUseApplicationBootstrapAndService(): void
    {
        foreach (['siswa/konsultasi.php', 'uks/jawab_konsultasi.php'] as $path) {
            $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
            self::assertStringContainsString("require_once '../bootstrap.php'", $contents);
            self::assertStringContainsString('ConsultationService', $contents);
        }
    }

    public function testServiceContainsOwnershipAndTransactionBoundaries(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/app/Services/ConsultationService.php');
        self::assertStringContainsString('beginTransaction', $contents);
        self::assertStringContainsString('FOR UPDATE', $contents);
        self::assertStringContainsString("u.role = 'siswa'", $contents);
        self::assertStringContainsString('mb_strlen', $contents);
    }
}
